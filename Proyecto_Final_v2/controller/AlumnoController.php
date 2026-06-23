<?php

class AlumnoController {
    public function dashboard() {
        require_auth(['alumno']);

        $alu_id = $_SESSION['usuario_id'];

        $asistenciaModel = new Asistencia();
        $inscripcionModel = new Inscripcion();
        $claseModel = new Clase();
        $usuarioModel = new Usuario();

        $pct_global = $asistenciaModel->getPromedioAlumno($alu_id);
        $presentes = $asistenciaModel->getPresentesAlumno($alu_id);
        $ausentes = $asistenciaModel->getAusentesAlumno($alu_id);
        $total_materias = $inscripcionModel->countByAlumno($alu_id);

        $clases_hoy = $claseModel->getTodayClassesForAlumno($alu_id);
        $recientes = $asistenciaModel->getRecientesAlumno($alu_id, 5);

        // Helpers
        $estado_class = ['en_curso' => 'state-encurso', 'pendiente' => 'state-pendiente', 'finalizada' => 'state-finalizada'];
        $badge_est    = ['presente' => ['badge-success','Presente'], 'tardanza' => ['badge-warning','Tardanza'], 'ausente' => ['badge-danger','Ausente']];
        $partes       = explode(' ', $_SESSION['nombre']);
        $iniciales    = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));
        $nombre_corto = $partes[0];

        $alumno = $usuarioModel->findById($alu_id);

        include dirname(__DIR__) . '/view/alumno/dashboard.php';
    }

    public function materias() {
        require_auth(['alumno']);

        $alu_id = $_SESSION['usuario_id'];

        $usuarioModel = new Usuario();
        $asistenciaModel = new Asistencia();

        $pdo = getPDO();
        $stmt = $pdo->prepare(
            "SELECT m.id, m.nombre, m.curso, m.modalidad,
                    COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
                    ROUND(
                      SUM(a.estado IN ('presente','tardanza'))
                      / NULLIF(COUNT(DISTINCT c.id), 0) * 100, 1
                    ) AS pct,
                    COUNT(DISTINCT c.id) AS total_clases
             FROM inscripciones i
             JOIN materias m ON m.id = i.materia_id
             LEFT JOIN usuarios u ON u.id = m.profesor_id
             LEFT JOIN clases c ON c.materia_id = m.id AND c.estado = 'finalizada'
             LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = i.alumno_id
             WHERE i.alumno_id = ?
             GROUP BY m.id
             ORDER BY m.nombre"
        );
        $stmt->execute([$alu_id]);
        $materias = $stmt->fetchAll();

        // Stats resumen
        $stmt = $pdo->prepare(
            "SELECT ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1)
             FROM asistencias a
             JOIN clases c ON c.id = a.clase_id
             JOIN inscripciones i ON i.materia_id = c.materia_id AND i.alumno_id = a.alumno_id
             WHERE a.alumno_id = ?"
        );
        $stmt->execute([$alu_id]);
        $pct_global = $stmt->fetchColumn() ?? 0;

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        $user = $usuarioModel->findById($alu_id);
        $curso = $user['curso'] ?? '';

        include dirname(__DIR__) . '/view/alumno/materias.php';
    }

    public function escanear() {
        require_auth(['alumno']);

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        include dirname(__DIR__) . '/view/alumno/escanear.php';
    }

    public function historial() {
        require_auth(['alumno']);

        $alu_id = $_SESSION['usuario_id'];

        $asistenciaModel = new Asistencia();
        $inscripcionModel = new Inscripcion();

        $f_materia = (int)($_GET['materia_id'] ?? 0) ?: null;
        $f_estado  = trim($_GET['estado'] ?? '');
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $por_pag   = 20;
        $offset    = ($pagina - 1) * $por_pag;

        $estados_validos = ['presente', 'ausente', 'tardanza'];
        if (!in_array($f_estado, $estados_validos, true)) $f_estado = '';

        // Materias del alumno para el filtro
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            "SELECT m.id, m.nombre FROM inscripciones i
             JOIN materias m ON m.id = i.materia_id
             WHERE i.alumno_id = ? ORDER BY m.nombre"
        );
        $stmt->execute([$alu_id]);
        $mis_materias = $stmt->fetchAll();

        $total = $asistenciaModel->countAllFilteredForAlumno($alu_id, $f_materia, $f_estado);
        $total_paginas = max(1, (int)ceil($total / $por_pag));

        $filas = $asistenciaModel->getAllFilteredForAlumno($alu_id, $f_materia, $f_estado, $por_pag, $offset);
        $stats = $asistenciaModel->getStatsForAlumno($alu_id, $f_materia);

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        $materia_nombre = '';
        if ($f_materia) {
            foreach ($mis_materias as $mm) {
                if ($mm['id'] == $f_materia) { $materia_nombre = $mm['nombre']; break; }
            }
        }

        $badge_estado = [
            'presente' => 'badge-success',
            'tardanza' => 'badge-warning',
            'ausente'  => 'badge-danger',
        ];
        $label_estado = [
            'presente' => 'Presente',
            'tardanza' => 'Tardanza',
            'ausente'  => 'Ausente',
        ];

        include dirname(__DIR__) . '/view/alumno/historial.php';
    }

    public function perfil() {
        require_auth(['alumno']);

        $usuarioModel = new Usuario();
        $user = $usuarioModel->findById($_SESSION['usuario_id']);

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        include dirname(__DIR__) . '/view/alumno/perfil.php';
    }
}
