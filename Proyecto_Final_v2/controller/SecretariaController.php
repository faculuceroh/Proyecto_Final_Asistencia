<?php

class SecretariaController {
    public function exportar() {
        require_auth(['secretaria', 'admin']);

        $materiaModel = new Materia();
        $usuarioModel = new Usuario();
        $claseModel = new Clase();
        $asistenciaModel = new Asistencia();

        $f_profesor = (int) ($_GET['profesor'] ?? 0) ?: null;
        $f_materia  = (int) ($_GET['materia']  ?? 0) ?: null;
        $f_fecha    = $_GET['fecha'] ?? '';
        $pagina     = max(1, (int) ($_GET['pagina'] ?? 1));
        $por_pag    = 15;
        $offset     = ($pagina - 1) * $por_pag;

        $profesores = $usuarioModel->getAll('', 'profesor');
        $materias_lista = $materiaModel->getAllActive();

        // Stats
        $total_clases = $claseModel->countAllFiltered(null, null, '', 'finalizada');
        $total_pres = $asistenciaModel->getTotalPresentes();
        $total_aus = $asistenciaModel->getTotalAusentes();
        $prom_asist = $asistenciaModel->getPromedioGlobal();

        $total = $claseModel->countAllFiltered($f_profesor, $f_materia, $f_fecha, 'finalizada');
        $total_paginas = max(1, (int) ceil($total / $por_pag));

        $clases = $claseModel->getAllFiltered($f_profesor, $f_materia, $f_fecha, 'finalizada', $por_pag, $offset);

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1) . substr($partes[1]??'',0,1));

        include dirname(__DIR__) . '/view/secretaria/exportar.php';
    }

    public function inscripciones() {
        require_auth(['secretaria', 'admin']);

        $materiaModel = new Materia();
        $inscripcionModel = new Inscripcion();
        $usuarioModel = new Usuario();

        $materias = $materiaModel->getAllActive();

        $sel_materia = (int)($_GET['materia_id'] ?? 0) ?: ($materias[0]['id'] ?? 0);

        $inscritos = [];
        $mat_info  = null;
        if ($sel_materia) {
            $mat_info = $materiaModel->findById($sel_materia);
            $inscritos = $inscripcionModel->getInscriptosByMateria($sel_materia);
        }

        $todos_alumnos = $usuarioModel->getAll('', 'alumno');

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        include dirname(__DIR__) . '/view/secretaria/inscripciones.php';
    }

    public function materias() {
        require_auth(['secretaria', 'admin']);

        $materiaModel = new Materia();
        $usuarioModel = new Usuario();

        $cursos = $materiaModel->getCursos();
        $profesores = $usuarioModel->getAll('', 'profesor');

        $pdo = getPDO();
        $materias = $pdo->query(
            "SELECT m.id, m.nombre, m.curso, m.modalidad,
                    COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
                    (SELECT GROUP_CONCAT(
                       CASE dia_semana
                         WHEN 1 THEN 'Lun' WHEN 2 THEN 'Mar' WHEN 3 THEN 'Mié'
                         WHEN 4 THEN 'Jue' WHEN 5 THEN 'Vie' WHEN 6 THEN 'Sáb' ELSE 'Dom'
                       END ORDER BY dia_semana SEPARATOR ', ')
                     FROM materia_horarios WHERE materia_id = m.id) AS dias,
                    (SELECT CONCAT(TIME_FORMAT(hora_inicio,'%H:%i'),' - ',TIME_FORMAT(hora_fin,'%H:%i'))
                     FROM materia_horarios WHERE materia_id = m.id LIMIT 1) AS horario
             FROM materias m
             LEFT JOIN usuarios u ON u.id = m.profesor_id
             WHERE m.activo = 1 ORDER BY m.nombre"
        )->fetchAll();

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        $dias_semana = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];

        include dirname(__DIR__) . '/view/secretaria/materias.php';
    }

    public function reportes() {
        require_auth(['secretaria', 'admin']);

        $asistenciaModel = new Asistencia();

        $prom_asist = $asistenciaModel->getPromedioGlobal();
        $total_pres = $asistenciaModel->getTotalPresentes();
        $total_aus = $asistenciaModel->getTotalAusentes();
        $en_riesgo = $asistenciaModel->getAlumnosEnRiesgoCount();

        $por_materia = $asistenciaModel->getAsistenciaPorMateriaLimit(8);
        $riesgo = $asistenciaModel->getAlumnosEnRiesgoLimit(10);

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        include dirname(__DIR__) . '/view/secretaria/reportes.php';
    }

    public function usuarios() {
        require_auth(['secretaria', 'admin']);

        $materiaModel = new Materia();

        $cursos = $materiaModel->getCursos();

        $partes    = explode(' ', $_SESSION['nombre']);
        $iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

        include dirname(__DIR__) . '/view/secretaria/usuarios.php';
    }
}
