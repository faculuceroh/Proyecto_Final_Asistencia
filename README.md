# 🛡️ Proyecto Final de Asistencia - UTN Haedo

Este proyecto implementa un sistema de gestión de presentismo utilizando una arquitectura **MVC (Modelo-Vista-Controlador)** con validaciones de seguridad avanzadas.

---

### ⚠️ NOTA DE DESARROLLO / AVISO IMPORTANTE:

> [!WARNING]
> **Compatibilidad del Login (Solo Administrador Principal)**
> Actualmente, el sistema **solo permite iniciar sesión de forma exitosa con el usuario Administrador Maestro** creado a través del script `crear_admin.php` (`admin_principal@admin.frh.utn.edu.ar` / `admin123`).
> 
> **¿Por qué?** El controlador de login utiliza la función segura `password_verify()` para validar los hashes matemáticos **BCRYPT**. Si se intentan insertar usuarios de prueba (Alumnos o Profesores) directamente escribiendo texto plano en las tablas de MySQL mediante phpMyAdmin, el login va a rebotar por **incompatibilidad de hash**.
> 
> *Para probar otros roles en el futuro, se deberán desarrollar primero las funciones de registro que encripten las contraseñas antes de guardarlas en la base de datos.*