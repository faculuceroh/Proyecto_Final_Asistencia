<?php

class ProfesorController {
    public function dashboard() {
        require_auth(['profesor']);

        $profesor_id = $_SESSION['usuario_id'];

        $claseModel = new Clase();
        $clases = $claseModel->getTodayClassesForProfesor($profesor_id);

        $estado_class = [
            'en_curso'   => 'state-encurso',
            'pendiente'  => 'state-pendiente',
            'finalizada' => 'state-finalizada',
        ];
        $estado_badge = [
            'en_curso'   => ['badge-warning', 'En curso'],
            'pendiente'  => ['badge-muted',   'Pendiente'],
            'finalizada' => ['badge-success', 'Finalizada'],
        ];

        $dias      = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
        $fecha_hoy = ucfirst($dias[date('w')]) . ' ' . date('d/m/Y');

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

        $en_curso  = count(array_filter($clases, fn($c) => $c['estado'] === 'en_curso'));

        include dirname(__DIR__) . '/view/profesor/dashboard.php';
    }

    public function materias() {
        require_auth(['profesor', 'admin']);

        $prof_id = $_SESSION['usuario_id'];

        $materiaModel = new Materia();
        $claseModel = new Clase();

        $mis_materias = $materiaModel->getMateriasByProfesor($prof_id);

        // Horarios semanales por materia
        $horarios_map = [];
        foreach ($mis_materias as $m) {
            $horarios_map[$m['id']] = [
                'modalidad' => $m['modalidad'],
                'horarios'  => $materiaModel->getHorariosByMateria($m['id']),
            ];
        }

        $clases = $claseModel->getClassesForProfesorLimit($prof_id, 30);

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        $estado_badge = [
            'pendiente'  => ['badge-muted',   'Pendiente'],
            'en_curso'   => ['badge-warning', 'En curso'],
            'finalizada' => ['badge-success', 'Finalizada'],
        ];

        include dirname(__DIR__) . '/view/profesor/materias.php';
    }

    public function generar_qr() {
        require_auth(['profesor', 'admin']);

        $clase_id = (int) ($_GET['clase'] ?? 0);
        if (!$clase_id) {
            header('Location: dashboard.php');
            exit;
        }

        $claseModel = new Clase();
        $clase = $claseModel->findById($clase_id);

        if (!$clase) {
            header('Location: dashboard.php');
            exit;
        }

        // Verify ownership/validity
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'SELECT c.id, c.aula, c.modalidad, c.estado,
                    m.nombre AS materia, m.curso,
                    (SELECT COUNT(*) FROM inscripciones WHERE materia_id = m.id) AS total_alumnos
             FROM clases c
             JOIN materias m ON m.id = c.materia_id
             WHERE c.id = ? AND m.profesor_id = ?
             LIMIT 1'
        );
        $stmt->execute([$clase_id, $_SESSION['usuario_id']]);
        $clase = $stmt->fetch();

        if (!$clase) {
            header('Location: dashboard.php');
            exit;
        }
        if ($clase['estado'] === 'finalizada') {
            header('Location: dashboard.php');
            exit;
        }

        $materia   = $_GET['materia']   ?? $clase['materia'];
        $grupo     = $_GET['grupo']     ?? $clase['curso'];
        $modalidad = $_GET['modalidad'] ?? $clase['modalidad'];
        $tipo      = in_array($_GET['tipo'] ?? '', ['entrada','salida']) ? $_GET['tipo'] : 'entrada';
        $aula      = $_GET['aula']      ?? $clase['aula'] ?? '';

        $lugar = $modalidad === 'virtual' ? 'Virtual' : ($aula ?: 'Presencial');
        $sub   = implode(' · ', array_filter([$grupo, $lugar, 'En curso']));

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

        include dirname(__DIR__) . '/view/profesor/generar_qr.php';
    }

    public function historial() {
        require_auth(['profesor', 'admin']);

        $prof_id = $_SESSION['usuario_id'];

        $materiaModel = new Materia();
        $asistenciaModel = new Asistencia();

        $f_materia = (int) ($_GET['materia'] ?? 0) ?: null;
        $f_fecha   = $_GET['fecha']  ?? '';
        $f_estado  = $_GET['estado'] ?? '';
        $pagina    = max(1, (int) ($_GET['pagina'] ?? 1));
        $por_pag   = 20;
        $offset    = ($pagina - 1) * $por_pag;

        $mis_materias = $materiaModel->getMateriasByProfesor($prof_id);

        $total         = $asistenciaModel->countAllFiltered($prof_id, $f_materia, $f_fecha, $f_estado);
        $total_paginas = max(1, (int) ceil($total / $por_pag));

        $st = $asistenciaModel->getStatsFiltered($prof_id, $f_materia, $f_fecha);
        $filas = $asistenciaModel->getAllFiltered($prof_id, $f_materia, $f_fecha, $f_estado, $por_pag, $offset);

        // URL export con filtros actuales
        $export_params = array_filter([
            'materia_id' => $f_materia,
            'fecha'      => $f_fecha,
        ]);
        if (!$export_params) $export_params['materia_id'] = $f_materia ?: ($mis_materias[0]['id'] ?? 0);
        $export_url = '../api/exportar.php?' . http_build_query($export_params);

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        $badge = ['presente' => 'badge-success', 'tardanza' => 'badge-warning', 'ausente' => 'badge-danger'];
        $label = ['presente' => 'Presente', 'tardanza' => 'Tardanza', 'ausente' => 'Ausente'];

        include dirname(__DIR__) . '/view/profesor/historial.php';
    }

    public function perfil() {
        require_auth(['profesor', 'admin']);

        $usuarioModel = new Usuario();
        $user = $usuarioModel->findById($_SESSION['usuario_id']);

        $materiaModel = new Materia();
        $materias = $materiaModel->getMateriasByProfesor($_SESSION['usuario_id']);

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        include dirname(__DIR__) . '/view/profesor/perfil.php';
    }
}
