<?php

class AuthController {

    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $legajo   = isset($_POST['legajo']) ? trim($_POST['legajo']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if ($legajo === '' || $password === '') {
                header("Location: ../index.php?error=1");
                exit();
            }

            try {
                $usuarioModel = new Usuario();
                $user = $usuarioModel->findByLegajo($legajo);
            } catch (PDOException $e) {
                header("Location: ../index.php?error=bd");
                exit();
            }

            // Validamos la contraseña contra el hash de la base de datos
            if (!$user || !password_verify($password, $user['password'])) {
                header("Location: ../index.php?error=1");
                exit();
            }

            // Validamos si el usuario está activo
            if (!(bool) $user['activo']) {
                header("Location: ../index.php?error=desactivado");
                exit();
            }

            // Guardamos las variables en la Sesión del Servidor
            $_SESSION['usuario_id'] = (int) $user['id'];
            $_SESSION['nombre']     = $user['nombre'] . ' ' . $user['apellido'];
            $_SESSION['rol']        = $user['rol'];

            // Mapeo tradicional de redirección según el rol
            $destinos = [
                'alumno'     => '../alumno/dashboard.php',
                'profesor'   => '../profesor/dashboard.php',
                'secretaria' => '../secretaria/exportar.php',
                'admin'      => '../admin/dashboard.php',
            ];

            $redirigir = isset($destinos[$user['rol']]) ? $destinos[$user['rol']] : '../index.php';
            
            header("Location: " . $redirigir);
            exit();

        } else {
            header("Location: ../index.php");
            exit();
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();
        header("Location: index.php");
        exit();
    }
}
