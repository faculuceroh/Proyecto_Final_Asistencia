<?php
/**
 * TeamsAttendanceParser
 *
 * Parsea el archivo .csv que exporta Microsoft Teams al finalizar una reunión.
 *
 * Formato real del archivo:
 *  - Codificación: UTF-16LE con BOM (0xFF 0xFE)
 *  - Separador:    TAB (no coma, a pesar de la extensión .csv)
 *  - 3 secciones:  "1. Resumen", "2. Participantes", "3. Actividades de la reunión"
 *  - Solo se usa la sección "2. Participantes"
 *
 * Uso:
 *   $participantes = TeamsAttendanceParser::parse('/ruta/al/archivo.csv');
 *   // Cada elemento: ['nombre', 'email', 'hora_entrada', 'hora_salida',
 *   //                 'duracion_minutos', 'rol_teams']
 */
class TeamsAttendanceParser
{
    /**
     * Parsea el archivo y devuelve los participantes de la sección 2.
     *
     * @param  string $filePath Ruta absoluta al archivo subido.
     * @return array[]          Array de participantes.
     * @throws RuntimeException Si el archivo no puede leerse o tiene formato inesperado.
     */
    public static function parse(string $filePath): array
    {
        // ── 1. Leer bytes crudos ──────────────────────────────────────────────
        $raw = @file_get_contents($filePath);
        if ($raw === false) {
            throw new RuntimeException("No se pudo leer el archivo: $filePath");
        }

        // ── 2. Quitar BOM UTF-16LE (0xFF 0xFE) y convertir a UTF-8 ───────────
        // Teams siempre exporta con BOM. Si no está, intentamos igual la conversión.
        if (str_starts_with($raw, "\xFF\xFE")) {
            $raw = substr($raw, 2);
        }
        $content = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');

        // ── 3. Normalizar saltos de línea ─────────────────────────────────────
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // ── 4. Separar en líneas ──────────────────────────────────────────────
        $lines = explode("\n", $content);

        // ── 5. Extraer solo las líneas de la sección "2. Participantes" ───────
        //   Buscamos la línea cuyo primer campo (antes del primer TAB) empiece
        //   con "2. Participantes". Terminamos en "3. " o en el fin del archivo.
        $section2Lines = [];
        $inside = false;

        foreach ($lines as $line) {
            // Primer campo de la línea (puede ser la única celda o la primera de varias)
            $firstField = trim(explode("\t", $line)[0]);

            if (!$inside) {
                // Detectar inicio de sección 2
                if (str_starts_with($firstField, '2. Participantes')) {
                    $inside = true;
                }
                continue;
            }

            // Detectar inicio de sección 3 (terminamos)
            if (preg_match('/^3\.\s/', $firstField)) {
                break;
            }

            $section2Lines[] = $line;
        }

        if (empty($section2Lines)) {
            throw new RuntimeException(
                'No se encontró la sección "2. Participantes" en el archivo. ' .
                'Verificá que el archivo sea el export de asistencia de Teams (no el de chat).'
            );
        }

        // ── 6. Encontrar la fila de encabezados ───────────────────────────────
        //   La buscamos por contenido, no por posición, por si Teams agrega
        //   líneas de metadata antes de los datos.
        $headerIdx = null;
        foreach ($section2Lines as $i => $line) {
            // El encabezado tiene "Nombre" y "Correo electrónico" separados por TAB
            if (str_contains($line, 'Nombre') && str_contains($line, 'Correo')) {
                $headerIdx = $i;
                break;
            }
        }

        if ($headerIdx === null) {
            throw new RuntimeException(
                'No se encontró la fila de encabezados en la sección "2. Participantes". ' .
                'El archivo puede estar en un idioma no soportado o con formato alterado.'
            );
        }

        // ── 7. Mapear columnas por nombre (no por posición) ───────────────────
        //   array_flip convierte [ 0=>'Nombre', 1=>'Primera entrada', ... ]
        //   en [ 'Nombre'=>0, 'Primera entrada'=>1, ... ]
        $rawHeaders = explode("\t", $section2Lines[$headerIdx]);
        $colMap = [];
        foreach ($rawHeaders as $i => $h) {
            $colMap[trim($h)] = $i;
        }

        // Columnas requeridas (nombres en español del export de Teams)
        $required = ['Nombre', 'Primera entrada', 'Última salida',
                     'Duración de la reunión', 'Correo electrónico', 'Rol'];
        foreach ($required as $col) {
            if (!array_key_exists($col, $colMap)) {
                throw new RuntimeException(
                    "Columna requerida no encontrada: \"$col\". " .
                    "¿El archivo está en español? Columnas detectadas: " .
                    implode(', ', array_keys($colMap))
                );
            }
        }

        // ── 8. Parsear filas de datos ─────────────────────────────────────────
        $participantes = [];

        for ($i = $headerIdx + 1; $i < count($section2Lines); $i++) {
            $line = $section2Lines[$i];
            if (trim($line) === '') {
                continue;
            }

            $cols = explode("\t", $line);

            $nombre    = trim($cols[$colMap['Nombre']]                  ?? '');
            $entrada   = trim($cols[$colMap['Primera entrada']]         ?? '');
            $salida    = trim($cols[$colMap['Última salida']]           ?? '');
            $duracion  = trim($cols[$colMap['Duración de la reunión']]  ?? '');
            $email     = trim($cols[$colMap['Correo electrónico']]      ?? '');
            $rol_teams = trim($cols[$colMap['Rol']]                     ?? '');

            if ($nombre === '') {
                continue;
            }

            $participantes[] = [
                'nombre'           => $nombre,
                'email'            => mb_strtolower($email, 'UTF-8'),
                'hora_entrada'     => self::parseDateTime($entrada),
                'hora_salida'      => self::parseDateTime($salida),
                'duracion_minutos' => self::parseDuracion($duracion),
                'rol_teams'        => $rol_teams,
            ];
        }

        if (empty($participantes)) {
            throw new RuntimeException(
                'La sección "2. Participantes" no contiene filas de datos. ' .
                'Verificá que la reunión haya tenido participantes.'
            );
        }

        return $participantes;
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    /**
     * Convierte una fecha/hora en formato US de Teams a "H:i:s".
     * Formato de entrada: "M/D/YY, h:mm:ss AM/PM"  →  "10:34:46"
     *
     * Devuelve null si la cadena está vacía o no puede parsearse.
     */
    private static function parseDateTime(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Formato real confirmado: "6/17/26, 10:34:46 AM"
        $dt = DateTime::createFromFormat('n/j/y, g:i:s A', $raw);
        if ($dt === false) {
            // Intento alternativo por si Teams cambia el separador de fecha
            $dt = DateTime::createFromFormat('n/j/Y, g:i:s A', $raw);
        }

        return $dt ? $dt->format('H:i:s') : null;
    }

    /**
     * Convierte una cadena de duración de Teams a minutos enteros.
     *
     * Formatos posibles: "1 h 47 min", "25 min 26 s", "1 h 48 min 58 s"
     * El separador puede ser espacio normal O non-breaking space (\xc2\xa0 en UTF-8).
     * Devuelve los segundos redondeados hacia abajo (floor de minutos).
     */
    private static function parseDuracion(string $raw): int
    {
        if (trim($raw) === '') {
            return 0;
        }

        // Normalizar non-breaking space (\xc2\xa0) a espacio normal.
        // PHP \s+ NO matchea \xa0, por eso hay que hacerlo antes del regex.
        $raw = str_replace("\xc2\xa0", ' ', $raw);
        $raw = trim($raw);

        $totalSegundos = 0;

        // Horas: "1 h" o "2h"
        if (preg_match('/(\d+)\s*h\b/i', $raw, $m)) {
            $totalSegundos += (int)$m[1] * 3600;
        }

        // Minutos: "47 min" o "47min"
        if (preg_match('/(\d+)\s*min\b/i', $raw, $m)) {
            $totalSegundos += (int)$m[1] * 60;
        }

        // Segundos: "26 s" o "26s" — se suman para el redondeo correcto
        if (preg_match('/(\d+)\s*s\b/i', $raw, $m)) {
            $totalSegundos += (int)$m[1];
        }

        // Floor: 25 min 59 s → 25 min (no contamos el minuto incompleto)
        return (int)floor($totalSegundos / 60);
    }
}
