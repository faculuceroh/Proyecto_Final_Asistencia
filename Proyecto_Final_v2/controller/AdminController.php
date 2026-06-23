<?php

class AdminController {
    public function dashboard() {
        require_auth(['admin']);

        $materiaModel = new Materia();
        $usuarioModel = new Usuario();
        $claseModel = new Clase();
        $asistenciaModel = new Asistencia();

        $cursos = $materiaModel->getCursos();

        // Stats
        $total_alumnos    = $usuarioModel->countActiveByRol('alumno');
        $total_profesores = $usuarioModel->countActiveByRol('profesor');
        $clases_hoy       = $claseModel->countToday();
        $asistencia_prom  = $asistenciaModel->getPromedioGlobal();

        // Tabla de usuarios (paginada + búsqueda)
        $por_pagina = 10;
        $pagina     = max(1, (int) ($_GET['pagina'] ?? 1));
        $buscar     = trim($_GET['buscar'] ?? '');
        $offset     = ($pagina - 1) * $por_pagina;

        $total_usuarios = $usuarioModel->countAll($buscar);
        $total_paginas  = (int) ceil($total_usuarios / $por_pagina);
        $usuarios = $usuarioModel->getAll($buscar, '', $por_pagina, $offset);

        // Helpers de presentación
        $badge_rol = [
            'alumno'     => 'badge-accent',
            'profesor'   => 'badge-muted',
            'secretaria' => 'badge-warning',
            'admin'      => 'badge-danger',
        ];
        $label_rol = [
            'alumno'     => 'Alumno',
            'profesor'   => 'Profesor',
            'secretaria' => 'Secretaría',
            'admin'      => 'Admin',
        ];

        // Fecha en español
        $dias   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
        $fecha_hoy = ucfirst($dias[date('w')]) . ' ' . date('d/m/Y');

        // Iniciales del usuario logueado
        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

        include dirname(__DIR__) . '/view/admin/dashboard.php';
    }

    public function usuarios() {
        require_auth(['admin']);

        $usuarioModel = new Usuario();

        $q        = trim($_GET['q']      ?? '');
        $rol      = trim($_GET['rol']    ?? '');
        $pagina   = max(1, (int) ($_GET['pagina'] ?? 1));
        $por_pag  = 15;
        $offset   = ($pagina - 1) * $por_pag;

        $roles_validos = ['alumno', 'profesor', 'secretaria', 'admin'];
        if (!in_array($rol, $roles_validos, true)) $rol = '';

        $total = $usuarioModel->countAll($q, $rol);
        $total_paginas = max(1, (int) ceil($total / $por_pag));

        $usuarios = $usuarioModel->getAll($q, $rol, $por_pag, $offset);

        $badge_rol = ['alumno'=>'badge-accent','profesor'=>'badge-muted','secretaria'=>'badge-warning','admin'=>'badge-danger'];
        $label_rol = ['alumno'=>'Alumno','profesor'=>'Profesor','secretaria'=>'Secretaría','admin'=>'Admin'];

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

        include dirname(__DIR__) . '/view/admin/usuarios.php';
    }

    public function materias() {
        require_auth(['admin']);

        $materiaModel = new Materia();

        $q       = trim($_GET['q']      ?? '');
        $curso   = trim($_GET['curso']  ?? '');
        $pagina  = max(1, (int) ($_GET['pagina'] ?? 1));
        $por_pag = 15;
        $offset  = ($pagina - 1) * $por_pag;

        $total_materias   = $materiaModel->countActive();
        $total_profesores = $materiaModel->countDistinctProfessors();
        $total_cursos     = $materiaModel->countDistinctCursos();

        $cursos_lista = $materiaModel->getCursos();

        $total         = $materiaModel->countAll($q, $curso);
        $total_paginas = max(1, (int) ceil($total / $por_pag));

        $materias = $materiaModel->getAll($q, $curso, $por_pag, $offset);

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

        include dirname(__DIR__) . '/view/admin/materias.php';
    }

    public function configuracion() {
        require_auth(['admin']);

        $configModel = new Configuracion();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $campos = [
                'nombre_institucion'    => trim($_POST['nombre_institucion']    ?? ''),
                'periodo_activo'        => trim($_POST['periodo_activo']        ?? ''),
                'qr_rotacion_segundos'  => max(10, min(120, (int) ($_POST['qr_rotacion_segundos'] ?? 30))),
                'tolerancia_minutos'    => max(0,  min(60,  (int) ($_POST['tolerancia_minutos']   ?? 10))),
                'notificaciones_activas'=> isset($_POST['notificaciones_activas']) ? '1' : '0',
            ];

            $configModel->updateConfig($campos);

            header('Location: configuracion.php?guardado=1');
            exit;
        }

        $config = $configModel->getAll();

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

        include dirname(__DIR__) . '/view/admin/configuracion.php';
    }
}
