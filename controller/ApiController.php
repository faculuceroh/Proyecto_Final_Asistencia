<?php

class ApiController {
    // Helper to parse JSON input
    private function getJsonBody() {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    // Helper to output JSON
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public function cambiarPassword() {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body   = $this->getJsonBody();
        $actual = $body['password_actual'] ?? '';
        $nueva  = $body['password_nueva']  ?? '';

        if (!$actual || !$nueva) {
            $this->jsonResponse(['message' => 'Completá los dos campos'], 400);
        }

        if (strlen($nueva) < 6) {
            $this->jsonResponse(['message' => 'La nueva contraseña debe tener al menos 6 caracteres'], 422);
        }

        $usuarioModel = new Usuario();
        $user = $usuarioModel->findById($_SESSION['usuario_id']);

        if (!$user || !password_verify($actual, $user['password'])) {
            $this->jsonResponse(['message' => 'La contraseña actual es incorrecta'], 401);
        }

        $nuevo_hash = password_hash($nueva, PASSWORD_BCRYPT);
        $usuarioModel->updatePassword($_SESSION['usuario_id'], $nuevo_hash);

        $this->jsonResponse(['ok' => true]);
    }

    public function actualizarEmail() {
        require_auth();   // cualquier usuario logueado puede editar su propio email

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body  = $this->getJsonBody();
        $email = trim($body['email'] ?? '');

        // Permitimos vaciar el email; si viene cargado, validamos el formato
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['message' => 'El email no tiene un formato válido'], 422);
        }

        $usuarioModel = new Usuario();

        try {
            $usuarioModel->updateEmail($_SESSION['usuario_id'], $email);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->jsonResponse(['message' => 'Ese email ya está en uso por otra cuenta'], 409);
            }
            $this->jsonResponse(['message' => 'Error de base de datos'], 500);
        }

        $this->jsonResponse(['ok' => true, 'email' => $email]);
    }

    public function crearClase() {
        require_auth(['profesor', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body        = $this->getJsonBody();
        $materia_id  = (int) ($body['materia_id']  ?? 0);
        $fecha       = $body['fecha']       ?? '';
        $hora_inicio = $body['hora_inicio'] ?? '';
        $duracion    = (int) ($body['duracion_min'] ?? 90);
        $aula        = trim($body['aula']   ?? '');
        $modalidad   = in_array($body['modalidad'] ?? '', ['presencial','virtual'])
                       ? $body['modalidad'] : 'presencial';

        if (!$materia_id || !$fecha || !$hora_inicio) {
            $this->jsonResponse(['message' => 'Materia, fecha y hora son obligatorios'], 400);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $this->jsonResponse(['message' => 'Formato de fecha inválido (YYYY-MM-DD)'], 422);
        }

        $materiaModel = new Materia();
        $materia = $materiaModel->findById($materia_id);

        if (!$materia || ((int)$materia['profesor_id'] !== (int)$_SESSION['usuario_id'] && $_SESSION['rol'] !== 'admin')) {
            $this->jsonResponse(['message' => 'No tenés permiso para crear clases en esa materia'], 403);
        }

        $claseModel = new Clase();
        $duplicate = $claseModel->checkDuplicate($materia_id, $fecha, $hora_inicio);
        if ($duplicate) {
            $this->jsonResponse(['message' => 'Ya existe una clase para esa materia en esa fecha y hora'], 409);
        }

        $clase_id = $claseModel->create($materia_id, $fecha, $hora_inicio, $duracion, $aula, $modalidad);

        $this->jsonResponse([
            'ok'    => true,
            'clase' => [
                'id'          => $clase_id,
                'materia'     => $materia['nombre'],
                'curso'       => $materia['curso'],
                'fecha'       => $fecha,
                'fecha_fmt'   => date('d/m/Y', strtotime($fecha)),
                'hora_inicio' => substr($hora_inicio, 0, 5),
                'duracion_min'=> $duracion,
                'aula'        => $aula ?: '—',
                'modalidad'   => $modalidad,
                'estado'      => 'pendiente',
            ],
        ]);
    }

    public function crearMateria() {
        require_auth(['admin', 'secretaria', 'profesor']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body      = $this->getJsonBody();
        $nombre    = trim($body['nombre']    ?? '');
        $codigo    = trim($body['codigo']    ?? '');
        $curso     = trim($body['curso']     ?? '');
        $modalidad = in_array($body['modalidad'] ?? '', ['presencial','virtual']) ? $body['modalidad'] : 'presencial';
        $horarios  = $body['horarios'] ?? [];

        $pid_raw     = $body['profesor_id'] ?? 0;
        $profesor_id = ($pid_raw === 'self')
            ? (int) $_SESSION['usuario_id']
            : ((int) $pid_raw ?: null);

        if (!$nombre || !$curso) {
            $this->jsonResponse(['message' => 'Nombre y curso son obligatorios'], 400);
        }

        if (!$codigo) {
            $palabras = preg_split('/\s+/', strtoupper($nombre));
            $codigo   = implode('', array_map(fn($p) => substr($p, 0, 3), $palabras));
        }

        $materiaModel = new Materia();
        
        try {
            $pdo = getPDO();
            $pdo->beginTransaction();

            $id = $materiaModel->create($nombre, $codigo, $curso, $modalidad, $profesor_id);

            // Inserta horarios semanales
            $dias_nombres = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
            $horario_txt  = [];
            if (!empty($horarios) && is_array($horarios)) {
                $ins_h = $pdo->prepare(
                    'INSERT IGNORE INTO materia_horarios (materia_id, dia_semana, hora_inicio, hora_fin)
                     VALUES (?, ?, ?, ?)'
                );
                foreach ($horarios as $h) {
                    $dia  = (int) ($h['dia']        ?? 0);
                    $hi   = $h['hora_inicio'] ?? '';
                    $hf   = $h['hora_fin']    ?? '';
                    if ($dia >= 1 && $dia <= 7 && $hi && $hf) {
                        $ins_h->execute([$id, $dia, $hi, $hf]);
                        $horario_txt[] = $dias_nombres[$dia];
                    }
                }
            }

            $pdo->commit();

            $profesor = '—';
            if ($profesor_id) {
                $usuarioModel = new Usuario();
                $p = $usuarioModel->findById($profesor_id);
                $profesor = $p ? $p['nombre'] . ' ' . $p['apellido'] : '—';
            }

            $hora_ref = !empty($horarios[0]) ? substr($horarios[0]['hora_inicio'],0,5).' - '.substr($horarios[0]['hora_fin'],0,5) : '—';

        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                $this->jsonResponse(['message' => 'El código "'.$codigo.'" ya está en uso'], 409);
            }
            $this->jsonResponse(['message' => 'Error de base de datos'], 500);
        }

        $this->jsonResponse([
            'ok'      => true,
            'materia' => [
                'id'          => $id,
                'nombre'      => $nombre,
                'codigo'      => $codigo,
                'curso'       => $curso,
                'modalidad'   => $modalidad,
                'profesor'    => $profesor,
                'dias'        => implode(', ', $horario_txt) ?: '—',
                'hora'        => $hora_ref,
            ],
        ]);
    }

    public function crearUsuario() {
        require_auth(['admin', 'secretaria']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body     = $this->getJsonBody();
        $tipo     = trim($body['tipo']     ?? '');
        $nombre   = trim($body['nombre']   ?? '');
        $apellido = trim($body['apellido'] ?? '');
        $legajo   = trim($body['legajo']   ?? '');
        $curso    = trim($body['curso']    ?? '');
        $email    = trim($body['email']    ?? '') ?: null;

        $roles_validos = ['alumno', 'profesor', 'secretaria', 'admin'];
        if (!in_array($tipo, $roles_validos, true)) {
            $this->jsonResponse(['message' => 'Tipo de usuario inválido'], 400);
        }
        if (!$nombre || !$apellido || !$legajo) {
            $this->jsonResponse(['message' => 'Nombre, apellido y legajo son obligatorios'], 400);
        }
        if ($tipo === 'alumno' && !$curso) {
            $this->jsonResponse(['message' => 'El curso es obligatorio para alumnos'], 400);
        }

        $password_hash = password_hash($legajo, PASSWORD_BCRYPT);
        $usuarioModel = new Usuario();

        try {
            $id = $usuarioModel->create($legajo, $nombre, $apellido, $email, $password_hash, $tipo, $curso);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->jsonResponse(['message' => 'El legajo ' . $legajo . ' ya existe'], 409);
            }
            $this->jsonResponse(['message' => 'Error de base de datos'], 500);
        }

        $this->jsonResponse([
            'ok' => true,
            'id' => $id,
            'message' => 'Usuario creado. Contraseña inicial: ' . $legajo,
        ]);
    }

    public function desinscribirAlumno() {
        require_auth(['admin', 'secretaria']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body       = $this->getJsonBody();
        $materia_id = (int) ($body['materia_id'] ?? 0);
        $legajo     = trim($body['legajo'] ?? '');

        if (!$materia_id || !$legajo) {
            $this->jsonResponse(['message' => 'materia_id y legajo son obligatorios'], 400);
        }

        $usuarioModel = new Usuario();
        $alumno = $usuarioModel->findByLegajo($legajo);

        if (!$alumno) {
            $this->jsonResponse(['message' => 'Alumno no encontrado'], 404);
        }

        $inscripcionModel = new Inscripcion();
        $deleted = $inscripcionModel->desinscribir($materia_id, $alumno['id']);

        if (!$deleted) {
            $this->jsonResponse(['message' => 'Inscripción no encontrada'], 404);
        }

        $this->jsonResponse(['ok' => true]);
    }

    public function enviarSecretaria() {
        require_auth(['admin', 'secretaria', 'profesor']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body     = $this->getJsonBody();
        $clase_id = (int) ($body['clase_id'] ?? 0);

        if (!$clase_id) {
            $this->jsonResponse(['message' => 'clase_id requerido'], 400);
        }

        $claseModel = new Clase();
        $clase = $claseModel->findById($clase_id);

        if (!$clase) {
            $this->jsonResponse(['message' => 'Clase no encontrada'], 404);
        }

        if ($clase['estado'] !== 'finalizada') {
            $this->jsonResponse(['message' => 'La clase aún no fue finalizada'], 422);
        }

        $this->jsonResponse(['ok' => true]);
    }

    public function exportar() {
        // En api/exportar.php de manera nativa, se descarga un archivo CSV.
        // Delegamos el flujo de exportar directamente al archivo público que a su vez llama a este o ejecuta la descarga.
        // Pero para mantener la consistencia MVC, podemos poner la lógica aquí y requerir/incluir este método.
        require_auth(['admin', 'secretaria', 'profesor']);

        $clase_id   = (int) ($_GET['clase_id']   ?? 0);
        $materia_id = (int) ($_GET['materia_id'] ?? 0);
        $fecha_get  = $_GET['fecha'] ?? '';

        if (!$clase_id && !$materia_id) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'clase_id o materia_id requerido']);
            exit;
        }

        $pdo = getPDO();

        if ($materia_id && !$clase_id) {
            $stmt = $pdo->prepare('SELECT nombre, curso FROM materias WHERE id = ? LIMIT 1');
            $stmt->execute([$materia_id]);
            $mat = $stmt->fetch();
            if (!$mat) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['message' => 'Materia no encontrada']);
                exit;
            }

            $where  = 'WHERE c.materia_id = ?';
            $params = [$materia_id];
            if ($fecha_get) { $where .= ' AND c.fecha = ?'; $params[] = $fecha_get; }

            $stmt = $pdo->prepare(
                "SELECT u.apellido, u.nombre, u.legajo, u.curso AS alumno_curso,
                        c.fecha,
                        COALESCE(TIME_FORMAT(a.hora_entrada,'%H:%i'),'—') AS entrada,
                        COALESCE(TIME_FORMAT(a.hora_salida, '%H:%i'),'—') AS salida,
                        COALESCE(a.estado,'ausente')                        AS estado
                 FROM asistencias a
                 JOIN usuarios u ON u.id = a.alumno_id
                 JOIN clases c ON c.id = a.clase_id
                 $where
                 ORDER BY c.fecha DESC, u.apellido, u.nombre"
            );
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

            $mat_slug = preg_replace('/[^a-z0-9]+/i', '_', $mat['nombre']);
            $suf      = $fecha_get ? str_replace('-', '', $fecha_get) : 'completo';
            $filename = "historial_{$mat_slug}_{$suf}.csv";

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache');
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Apellido','Nombre','Legajo','Curso','Fecha','Entrada','Salida','Estado'], ';');
            foreach ($filas as $f) {
                fputcsv($out, [
                    $f['apellido'], $f['nombre'], $f['legajo'], $f['alumno_curso'],
                    date('d/m/Y', strtotime($f['fecha'])),
                    $f['entrada'], $f['salida'], ucfirst($f['estado']),
                ], ';');
            }
            fclose($out);
            exit;
        }

        // Modo: exportar clase específica
        $stmt = $pdo->prepare(
            'SELECT m.nombre AS materia, m.curso, c.fecha
             FROM clases c JOIN materias m ON m.id = c.materia_id
             WHERE c.id = ? LIMIT 1'
        );
        $stmt->execute([$clase_id]);
        $clase = $stmt->fetch();

        if (!$clase) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Clase no encontrada']);
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT u.apellido, u.nombre, u.legajo, u.curso,
                    COALESCE(TIME_FORMAT(a.hora_entrada,"%H:%i"), "—") AS entrada,
                    COALESCE(TIME_FORMAT(a.hora_salida, "%H:%i"), "—") AS salida,
                    COALESCE(a.estado, "ausente")                       AS estado
             FROM inscripciones i
             JOIN usuarios u ON u.id = i.alumno_id
             LEFT JOIN asistencias a ON a.alumno_id = i.alumno_id AND a.clase_id = ?
             WHERE i.materia_id = (SELECT materia_id FROM clases WHERE id = ?)
             ORDER BY u.apellido, u.nombre'
        );
        $stmt->execute([$clase_id, $clase_id]);
        $filas = $stmt->fetchAll();

        $fecha_str = str_replace('-', '', $clase['fecha']);
        $mat_slug  = preg_replace('/[^a-z0-9]+/i', '_', $clase['materia']);
        $filename  = "asistencia_{$mat_slug}_{$fecha_str}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Apellido','Nombre','Legajo','Curso','Entrada','Salida','Estado'], ';');
        foreach ($filas as $f) {
            fputcsv($out, [
                $f['apellido'], $f['nombre'], $f['legajo'],
                $f['curso'],    $f['entrada'], $f['salida'],
                ucfirst($f['estado']),
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function finalizar() {
        require_auth(['profesor', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body     = $this->getJsonBody();
        $clase_id = (int) ($body['clase_id'] ?? 0);

        if (!$clase_id) {
            $this->jsonResponse(['message' => 'clase_id requerido'], 400);
        }

        $claseModel = new Clase();
        $clase = $claseModel->findById($clase_id);

        if (!$clase) {
            $this->jsonResponse(['message' => 'Clase no encontrada'], 404);
        }
        if ($clase['estado'] === 'finalizada') {
            $this->jsonResponse(['message' => 'La clase ya estaba finalizada'], 409);
        }

        $total_ausentes = $claseModel->finalizar($clase_id, $clase['materia_id']);

        $this->jsonResponse([
            'ok'      => true,
            'ausentes' => $total_ausentes,
        ]);
    }

    public function importarAlumnos() {
        require_auth(['admin', 'secretaria']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['message' => 'No se recibió ningún archivo válido'], 400);
        }

        $archivo  = $_FILES['archivo'];
        $ext      = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx'], true)) {
            $this->jsonResponse(['message' => 'Solo se aceptan archivos .csv o .xlsx'], 400);
        }

        $filas = [];
        if ($ext === 'csv') {
            $filas = parsear_csv($archivo['tmp_name']);
        } else {
            $filas = parsear_xlsx($archivo['tmp_name']);
        }

        if (empty($filas)) {
            $this->jsonResponse(['message' => 'El archivo está vacío o no tiene el formato correcto'], 422);
        }

        $usuarioModel = new Usuario();
        $creados  = 0;
        $errores  = [];

        $primera = $filas[0];
        $inicio  = (isset($primera[2]) && !is_numeric(trim($primera[2]))) ? 1 : 0;

        for ($i = $inicio; $i < count($filas); $i++) {
            $fila = $filas[$i];

            $nombre   = trim($fila[0] ?? '');
            $apellido = trim($fila[1] ?? '');
            $legajo   = trim($fila[2] ?? '');
            $curso    = trim($fila[3] ?? '');
            $email    = trim($fila[4] ?? '') ?: null;

            if (!$nombre || !$apellido || !$legajo) {
                $errores[] = 'Fila ' . ($i + 1) . ': faltan datos obligatorios (nombre, apellido, legajo)';
                continue;
            }

            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'Fila ' . ($i + 1) . ' (legajo ' . $legajo . '): email inválido — ' . $email;
                continue;
            }

            $password_hash = password_hash($legajo, PASSWORD_BCRYPT);

            try {
                $usuarioModel->create($legajo, $nombre, $apellido, $email, $password_hash, 'alumno', $curso);
                $creados++;
            } catch (PDOException $e) {
                $errores[] = 'Fila ' . ($i + 1) . ' (legajo ' . $legajo . '): error al insertar / duplicado';
            }
        }

        $this->jsonResponse([
            'ok'      => true,
            'creados' => $creados,
            'errores' => $errores,
        ]);
    }

    public function inscribirAlumnos() {
        require_auth(['admin', 'secretaria']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $es_archivo = !empty($_FILES['archivo']['tmp_name']);

        if ($es_archivo) {
            $materia_id = (int) ($_POST['materia_id'] ?? 0);
        } else {
            $body       = $this->getJsonBody();
            $materia_id = (int) ($body['materia_id'] ?? 0);
            $legajos_manual = array_filter(array_map('trim', (array)($body['legajos'] ?? [])));
        }

        if (!$materia_id) {
            $this->jsonResponse(['message' => 'materia_id requerido'], 400);
        }

        $materiaModel = new Materia();
        $materia = $materiaModel->findById($materia_id);
        if (!$materia) {
            $this->jsonResponse(['message' => 'Materia no encontrada'], 404);
        }

        $legajos = [];
        if ($es_archivo) {
            $tmp  = $_FILES['archivo']['tmp_name'];
            $ext  = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));

            if ($ext === 'csv') {
                $filas_csv = parsear_csv($tmp);
                $primera = true;
                foreach ($filas_csv as $row) {
                    if (empty($row)) continue;
                    $val = trim($row[0]);
                    if ($primera && !is_numeric($val)) { $primera = false; continue; }
                    $primera = false;
                    if ($val !== '') $legajos[] = $val;
                }
            } elseif ($ext === 'xlsx') {
                $filas_xlsx = parsear_xlsx($tmp);
                $primera = true;
                foreach ($filas_xlsx as $row) {
                    if (empty($row)) continue;
                    $val = trim($row[0]);
                    if ($primera && !is_numeric($val)) { $primera = false; continue; }
                    $primera = false;
                    if ($val !== '') $legajos[] = $val;
                }
            } else {
                $this->jsonResponse(['message' => 'Formato no soportado. Usá CSV o XLSX.'], 422);
            }
        } else {
            $legajos = $legajos_manual;
        }

        if (empty($legajos)) {
            $this->jsonResponse(['message' => 'No se encontraron legajos en el archivo o la lista está vacía'], 400);
        }

        $inscritos      = 0;
        $no_encontrados = [];
        $ya_inscritos   = 0;

        $usuarioModel = new Usuario();
        $inscripcionModel = new Inscripcion();

        foreach ($legajos as $legajo) {
            $alumno = $usuarioModel->findByLegajo($legajo);
            if (!$alumno) {
                $no_encontrados[] = $legajo;
                continue;
            }
            if ($inscripcionModel->checkInscrito($materia_id, $alumno['id'])) {
                $ya_inscritos++;
            } else {
                $inscripcionModel->inscribir($materia_id, $alumno['id']);
                $inscritos++;
            }
        }

        $this->jsonResponse([
            'ok'             => true,
            'inscritos'      => $inscritos,
            'ya_inscritos'   => $ya_inscritos,
            'no_encontrados' => $no_encontrados,
        ]);
    }

    public function presentes() {
        require_auth(['profesor', 'admin']);

        $clase_id = (int) ($_GET['clase_id'] ?? 0);
        if (!$clase_id) {
            $this->jsonResponse(['message' => 'clase_id requerido'], 400);
        }

        $pdo = getPDO();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM inscripciones i
             JOIN clases c ON c.materia_id = i.materia_id
             WHERE c.id = ?'
        );
        $stmt->execute([$clase_id]);
        $total = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM asistencias
             WHERE clase_id = ? AND hora_entrada IS NOT NULL'
        );
        $stmt->execute([$clase_id]);
        $presentes = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT u.nombre, u.apellido,
                    TIME_FORMAT(a.hora_entrada, "%H:%i") AS hora
             FROM asistencias a
             JOIN usuarios u ON u.id = a.alumno_id
             WHERE a.clase_id = ? AND a.hora_entrada IS NOT NULL
             ORDER BY a.updated_at DESC
             LIMIT 5'
        );
        $stmt->execute([$clase_id]);
        $ultimos = array_map(function ($row) {
            return [
                'nombre'    => $row['nombre'] . ' ' . $row['apellido'],
                'iniciales' => strtoupper(substr($row['nombre'], 0, 1) . substr($row['apellido'], 0, 1)),
                'hora'      => $row['hora'],
            ];
        }, $stmt->fetchAll());

        $this->jsonResponse([
            'presentes' => $presentes,
            'total'     => $total,
            'ultimos'   => $ultimos,
        ]);
    }

    public function registrar() {
        require_auth(['alumno']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body     = $this->getJsonBody();
        $clase_id = (int)  ($body['clase_id'] ?? 0);
        $token    = trim($body['token']    ?? '');
        $tipo     = in_array($body['tipo'] ?? '', ['entrada', 'salida'], true)
                    ? $body['tipo'] : 'entrada';

        if (!$clase_id || !$token) {
            $this->jsonResponse(['message' => 'Datos incompletos'], 400);
        }

        $alumno_id = $_SESSION['usuario_id'];

        $qrTokenModel = new QrToken();
        if (!$qrTokenModel->verifyToken($token, $clase_id)) {
            $this->jsonResponse(['message' => 'El QR expiró. Esperá que el profesor lo renueve.'], 422);
        }

        $inscripcionModel = new Inscripcion();
        if (!$inscripcionModel->checkInscrito((new Clase())->findById($clase_id)['materia_id'] ?? 0, $alumno_id)) {
            $this->jsonResponse(['message' => 'No estás inscripto en esta materia'], 403);
        }

        $claseModel = new Clase();
        $clase = $claseModel->findById($clase_id);

        if (!$clase || $clase['estado'] === 'finalizada') {
            $this->jsonResponse(['message' => 'La clase ya fue finalizada'], 403);
        }

        $asistenciaModel = new Asistencia();
        $existente = $asistenciaModel->checkAsistencia($alumno_id, $clase_id);

        if ($tipo === 'entrada' && $existente && $existente['hora_entrada']) {
            $this->jsonResponse([
                'ok'   => true,
                'hora' => substr($existente['hora_entrada'], 0, 5),
                'aviso' => 'Ya tenías la entrada registrada.',
            ]);
        }
        if ($tipo === 'salida' && $existente && $existente['hora_salida']) {
            $this->jsonResponse([
                'ok'   => true,
                'hora' => substr($existente['hora_salida'], 0, 5),
                'aviso' => 'Ya tenías la salida registrada.',
            ]);
        }

        $hora_ahora = date('H:i:s');

        if ($tipo === 'entrada') {
            $configModel = new Configuracion();
            $tolerancia = (int) $configModel->getValue('tolerancia_minutos', 10);
            $hora_limite = date('H:i:s', strtotime($clase['hora_inicio']) + $tolerancia * 60);
            $estado      = ($hora_ahora <= $hora_limite) ? 'presente' : 'tardanza';

            $asistenciaModel->registrarEntrada($alumno_id, $clase_id, $estado);
        } else {
            $asistenciaModel->registrarSalida($alumno_id, $clase_id);
        }

        $this->jsonResponse([
            'ok'   => true,
            'hora' => date('H:i'),
        ]);
    }

    public function toggleUsuario() {
        require_auth(['admin', 'secretaria']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $body       = $this->getJsonBody();
        $usuario_id = (int) ($body['usuario_id'] ?? 0);
        $activo     = (int) ($body['activo']     ?? 0);

        if (!$usuario_id) {
            $this->jsonResponse(['message' => 'usuario_id requerido'], 400);
        }

        $usuarioModel = new Usuario();
        $usuarioModel->toggleActivo($usuario_id, $activo);

        $this->jsonResponse(['ok' => true]);
    }

    public function token() {
        require_auth(['profesor', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['message' => 'Método no permitido'], 405);
        }

        $clase_id = (int) ($_GET['clase_id'] ?? 0);
        if (!$clase_id) {
            $this->jsonResponse(['message' => 'clase_id requerido'], 400);
        }

        $claseModel = new Clase();
        $clase = $claseModel->findById($clase_id);

        if (!$clase) {
            $this->jsonResponse(['message' => 'Clase no encontrada'], 404);
        }
        if ($clase['estado'] === 'finalizada') {
            $this->jsonResponse(['message' => 'La clase ya fue finalizada'], 403);
        }

        $configModel = new Configuracion();
        $rotacion = (int) $configModel->getValue('qr_rotacion_segundos', 30);

        $qrTokenModel = new QrToken();
        $qrTokenModel->deactivateAllForClass($clase_id);

        $token = bin2hex(random_bytes(4));
        $qrTokenModel->createToken($clase_id, $token, $rotacion);

        if ($clase['estado'] === 'pendiente') {
            $claseModel->updateEstado($clase_id, 'en_curso');
        }

        $this->jsonResponse(['token' => $token]);
    }
}

// Global utility helper functions used by the XLSX/CSV parsers inside this class
function parsear_csv(string $ruta): array
{
    $filas = [];
    if (($h = fopen($ruta, 'r')) === false) return $filas;

    $bom = fread($h, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($h);

    $primera_linea = fgets($h);
    rewind($h);
    if ($bom === "\xEF\xBB\xBF") fread($h, 3);
    $delimitador = ';';
    if (substr_count($primera_linea, ',') > substr_count($primera_linea, ';')) $delimitador = ',';
    if (substr_count($primera_linea, "\t") > substr_count($primera_linea, $delimitador)) $delimitador = "\t";

    while (($fila = fgetcsv($h, 0, $delimitador)) !== false) {
        if (array_filter($fila)) {
            $filas[] = $fila;
        }
    }
    fclose($h);
    return $filas;
}

function col_idx(string $col): int
{
    $idx = 0;
    foreach (str_split($col) as $c) {
        $idx = $idx * 26 + (ord($c) - ord('A') + 1);
    }
    return $idx - 1;
}

function parsear_xlsx(string $ruta): array
{
    $filas = [];

    $zip = new ZipArchive();
    if ($zip->open($ruta) !== true) return $filas;

    $strings = [];
    $shared  = $zip->getFromName('xl/sharedStrings.xml');
    if ($shared) {
        $shared = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $shared);
        $xml    = simplexml_load_string($shared);
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
            } else {
                $txt = '';
                foreach ($si->r as $r) $txt .= (string) $r->t;
                $strings[] = $txt;
            }
        }
    }

    $hoja = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$hoja) return $filas;

    $hoja = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $hoja);
    $xml  = simplexml_load_string($hoja);
    foreach ($xml->sheetData->row as $row) {
        $fila = [];
        foreach ($row->c as $c) {
            preg_match('/^([A-Z]+)/', (string)($c['r'] ?? 'A'), $m);
            $col  = col_idx($m[1] ?? 'A');
            $tipo = (string)($c['t'] ?? '');

            if ($tipo === 's') {
                $valor = $strings[(int)((string)($c->v ?? ''))] ?? '';
            } elseif ($tipo === 'inlineStr') {
                $valor = (string)($c->is->t ?? '');
            } elseif ($tipo === 'str') {
                $valor = (string)($c->v ?? '');
            } else {
                $valor = (string)($c->v ?? '');
            }

            while (count($fila) < $col) $fila[] = '';
            $fila[] = $valor;
        }
        if (array_filter($fila)) {
            $filas[] = $fila;
        }
    }

    return $filas;
}
