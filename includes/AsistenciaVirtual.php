<?php
/**
 * AsistenciaVirtual
 *
 * Recibe el array de participantes del parser + un clase_id, y devuelve
 * tres grupos sin tocar la base de datos:
 *
 *   'importados'    → match confirmado por email + inscripto en la materia
 *   'advertencias'  → match por nombre (requiere confirmación) O no inscripto
 *   'no_matcheados' → no se encontró ningún alumno
 *
 * La inserción real en `asistencias` ocurre en un paso separado, después
 * de que el profesor confirme el resumen.
 */
class AsistenciaVirtual
{
    private PDO $db;
    private int $pctMinimo;      // Por debajo → ausente
    private int $pctTardanza;    // Por debajo → tardanza (si no ausente)
    private int $toleranciaMin;  // Minutos de gracia para hora de entrada

    public function __construct(PDO $db)
    {
        $this->db = $db;

        // Leer los tres umbrales de configuracion en una sola query
        $stmt = $db->prepare(
            "SELECT clave, valor FROM configuracion
             WHERE clave IN (
               'asistencia_virtual_porcentaje_minimo',
               'asistencia_virtual_porcentaje_tardanza',
               'tolerancia_minutos'
             )"
        );
        $stmt->execute();
        $cfg = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->pctMinimo     = (int)($cfg['asistencia_virtual_porcentaje_minimo']   ?? 30);
        $this->pctTardanza   = (int)($cfg['asistencia_virtual_porcentaje_tardanza'] ?? 70);
        $this->toleranciaMin = (int)($cfg['tolerancia_minutos']                     ?? 10);
    }

    /**
     * Procesa el array de participantes para una clase dada.
     *
     * @param  array[] $participantes  Salida de TeamsAttendanceParser::parse()
     * @param  int     $clase_id
     * @return array{
     *   importados:    array[],
     *   advertencias:  array[],
     *   no_matcheados: array[],
     *   clase:         array,
     * }
     */
    public function procesar(array $participantes, int $clase_id): array
    {
        $clase = $this->getClase($clase_id);

        $importados    = [];
        $advertencias  = [];
        $noMatcheados  = [];

        foreach ($participantes as $p) {
            // ── 1. Matching ─────────────────────────────────────────────────
            $matchTipo = null;
            $usuario   = null;

            if ($p['email'] !== '') {
                $usuario = $this->buscarPorEmail($p['email']);
                if ($usuario) {
                    $matchTipo = 'email';
                }
            }

            if (!$usuario) {
                $usuario = $this->sugerirPorNombre($p['nombre']);
                if ($usuario) {
                    $matchTipo = 'sugerido';
                }
            }

            // ── 2. Excluir no-alumnos (organizador, presentador, etc.) ──────
            //   Se hace silenciosamente: el profesor no necesita ver su propio
            //   registro en el resumen de alumnos.
            if ($usuario && $usuario['rol'] !== 'alumno') {
                continue;
            }

            // ── 3. Sin match → no_matcheados ────────────────────────────────
            if (!$usuario) {
                $noMatcheados[] = [
                    'nombre'      => $p['nombre'],
                    'email'       => $p['email'],
                    'hora_entrada'=> $p['hora_entrada'],
                    'hora_salida' => $p['hora_salida'],
                    'duracion_minutos' => $p['duracion_minutos'],
                    'rol_teams'   => $p['rol_teams'],
                ];
                continue;
            }

            // ── 4. Calcular estado ───────────────────────────────────────────
            $estado = $this->calcularEstado($p, $clase);

            $resultado = [
                'alumno_id'        => (int)$usuario['id'],
                'alumno_nombre'    => $usuario['nombre'] . ' ' . $usuario['apellido'],
                'alumno_legajo'    => $usuario['legajo'],
                'nombre_teams'     => $p['nombre'],
                'email'            => $p['email'],
                'hora_entrada'     => $p['hora_entrada'],
                'hora_salida'      => $p['hora_salida'],
                'duracion_minutos' => $p['duracion_minutos'],
                'estado'           => $estado,
                'clase_id'         => $clase_id,
                'match_tipo'       => $matchTipo,
            ];

            // ── 5. Verificar inscripción ─────────────────────────────────────
            $inscripto = $this->isInscripto($usuario['id'], $clase['materia_id']);

            if (!$inscripto) {
                $resultado['advertencia'] = 'No está inscripto en esta materia';
                $advertencias[] = $resultado;
            } elseif ($matchTipo === 'sugerido') {
                $resultado['advertencia'] = 'Match por nombre (requiere confirmación manual)';
                $advertencias[] = $resultado;
            } else {
                $importados[] = $resultado;
            }
        }

        return [
            'importados'    => $importados,
            'advertencias'  => $advertencias,
            'no_matcheados' => $noMatcheados,
            'clase'         => $clase,
        ];
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function getClase(int $clase_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.materia_id, c.fecha, c.hora_inicio, c.duracion_min, m.nombre AS materia_nombre
             FROM clases c
             JOIN materias m ON m.id = c.materia_id
             WHERE c.id = ? LIMIT 1'
        );
        $stmt->execute([$clase_id]);
        $clase = $stmt->fetch();

        if (!$clase) {
            throw new RuntimeException("Clase no encontrada: $clase_id");
        }

        return $clase;
    }

    private function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre, apellido, legajo, rol
             FROM usuarios
             WHERE LOWER(email) = LOWER(?) AND activo = 1
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Busca un alumno comparando nombre normalizado en ambos órdenes:
     * "Nombre Apellido" y "Apellido Nombre" (Teams suele exportar en mayúsculas).
     * NUNCA auto-confirma — siempre devuelve match_tipo = 'sugerido'.
     */
    private function sugerirPorNombre(string $nombreTeams): ?array
    {
        $buscado = $this->normalizar($nombreTeams);
        if ($buscado === '') {
            return null;
        }

        // Carga todos los alumnos activos (razonable para instituciones educativas)
        $stmt = $this->db->prepare(
            'SELECT id, nombre, apellido, legajo, rol
             FROM usuarios
             WHERE activo = 1 AND rol = "alumno"'
        );
        $stmt->execute();
        $alumnos = $stmt->fetchAll();

        foreach ($alumnos as $u) {
            $ordenA = $this->normalizar($u['nombre'] . ' ' . $u['apellido']);
            $ordenB = $this->normalizar($u['apellido'] . ' ' . $u['nombre']);

            if ($buscado === $ordenA || $buscado === $ordenB) {
                return $u;
            }
        }

        return null;
    }

    /**
     * Normaliza una cadena para comparación: minúsculas, sin tildes, sin puntuación.
     * Usa tabla de reemplazos explícita (sin iconv) para portabilidad en XAMPP Windows.
     */
    private function normalizar(string $str): string
    {
        $str = mb_strtolower($str, 'UTF-8');

        $desde = ['á','é','í','ó','ú','ü','ñ','à','â','ã','ä','å',
                  'è','ê','ë','ì','î','ï','ò','ô','õ','ö','ù','û',
                  'ý','ç','ß'];
        $hasta = ['a','e','i','o','u','u','n','a','a','a','a','a',
                  'e','e','e','i','i','i','o','o','o','o','u','u',
                  'y','c','ss'];

        $str = str_replace($desde, $hasta, $str);
        $str = preg_replace('/[^a-z0-9\s]/u', '', $str);
        $str = preg_replace('/\s+/', ' ', trim($str));

        return $str;
    }

    /**
     * Calcula el estado de asistencia aplicando dos criterios independientes;
     * prevalece el más restrictivo.
     *
     * Criterio 1 — porcentaje de tiempo conectado:
     *   < pctMinimo    → ausente
     *   < pctTardanza  → tardanza
     *   >= pctTardanza → presente
     *
     * Criterio 2 — entrada tardía:
     *   Si hora_entrada > hora_inicio + toleranciaMin, baja a tardanza como mínimo.
     *   (Solo aplica si el criterio 1 dio 'presente'; 'ausente' es absoluto.)
     */
    private function calcularEstado(array $p, array $clase): string
    {
        $duracionClase = (int)$clase['duracion_min'];

        if ($duracionClase <= 0) {
            return 'ausente';
        }

        $duracionTeams = (int)$p['duracion_minutos'];
        $porcentaje    = ($duracionTeams / $duracionClase) * 100;

        // ── Criterio 1: porcentaje ───────────────────────────────────────────
        if ($porcentaje < $this->pctMinimo) {
            return 'ausente'; // Absoluto: no se puede mejorar por el criterio 2
        }

        $estado = ($porcentaje < $this->pctTardanza) ? 'tardanza' : 'presente';

        // ── Criterio 2: tardanza por hora de entrada ─────────────────────────
        //   Solo baja de 'presente' a 'tardanza', nunca sube.
        if ($estado === 'presente' && $p['hora_entrada'] !== null) {
            $minEntrada = $this->timeToMinutes($p['hora_entrada']);
            $minInicio  = $this->timeToMinutes($clase['hora_inicio']);

            if ($minEntrada !== null && $minInicio !== null) {
                if (($minEntrada - $minInicio) > $this->toleranciaMin) {
                    $estado = 'tardanza';
                }
            }
        }

        return $estado;
    }

    /**
     * Convierte "H:i:s" a minutos totales desde medianoche.
     * Evita problemas de timezone que tendría DateTime::getTimestamp().
     */
    private function timeToMinutes(string $time): ?int
    {
        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return null;
        }
        return (int)$parts[0] * 60 + (int)$parts[1];
    }

    private function isInscripto(int $alumno_id, int $materia_id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM inscripciones WHERE alumno_id = ? AND materia_id = ? LIMIT 1'
        );
        $stmt->execute([$alumno_id, $materia_id]);
        return (bool)$stmt->fetch();
    }
}
