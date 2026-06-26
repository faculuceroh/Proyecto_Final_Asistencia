# Frontend — Sistema de Asistencia QR

Frontend en **HTML5 + CSS3 + JavaScript vanilla** (sin frameworks, sin npm, sin build).
Funciona con archivos estáticos servidos por PHP (XAMPP / hosting compartido).

## Estructura

```
assets/
├── css/
│   ├── main.css        Variables, reset, botones, badges, toasts, spinner, estados vacíos
│   ├── auth.css        Login (selector de rol, card centrada)
│   ├── dashboard.css   Layout de paneles: sidebar fija + hamburger, tablas, cards, paginación
│   └── qr.css          Generador de QR (profesor) y escáner con teclado numérico (alumno)
├── js/
│   ├── utils.js        App.toast(), App.api(), loader, sidebar móvil, helpers
│   ├── qr-display.js   QR rotativo cada 30s + countdown + contador de presentes en vivo
│   ├── qr-scanner.js   Escáner de QR con cámara (jsQR) + POST a api/registrar.php + éxito/error
│   └── export.js       Exportar a Excel (descarga) y "Enviar a secretaría" por data-attrs
└── img/
    └── logo.png        Placeholder de logo institucional

Pantallas (frontend estático en .html; el .php lo agregás vos a mano):
  index.html                  → Login (rol detectado por legajo)

  alumno/dashboard.html       → Panel del alumno (resumen + clases de hoy + historial)
  alumno/materias.html        → Materias en las que está anotado (asistencia x materia)
  alumno/escanear.html        → Escáner de QR con cámara (registra entrada/salida)
  alumno/perfil.html          → Datos personales + contraseña

  profesor/dashboard.html     → Clases del día
  profesor/materias.html      → Clases que dicta (crear + alumnos por materia)
  profesor/generar_qr.html    → QR rotativo (recibe modalidad + tipo del pop-up del dashboard)
  profesor/historial.html     → Tabla con filtros + paginación
  profesor/perfil.html        → Datos personales + materias

  secretaria/exportar.html    → Clases del período, exportar/enviar
  secretaria/materias.html    → Crear materias (asignando profesor)
  secretaria/usuarios.html    → Alta de alumno / profesor / secretaría + import Excel
  secretaria/reportes.html    → Estadísticas: asistencia x materia, alumnos en riesgo

  admin/dashboard.html        → Cards de resumen + usuarios + alta rápida
  admin/usuarios.html         → Gestión de usuarios (buscar, filtrar, activar/desactivar)
  admin/materias.html         → Todas las materias del período
  admin/configuracion.html    → Parámetros del sistema (QR, notificaciones, institución)
```

## Login por legajo

El usuario **ya no elige su rol**: se reconoce por el legajo. En el demo la
convención es el primer dígito (`2`=alumno, `1`=profesor, `3`=secretaría, `9`=admin);
en producción lo determina el backend en `login.php`. Probá: `20451`, `10245`, `30012`.

## QR de entrada y de salida

En `profesor/generar_qr.*` hay un toggle **Entrada / Salida**. El tipo viaja en la
URL del QR (`...escanear.php?clase=X&tipo=entrada|salida&t=TOKEN`) y en el body del
POST a `registrar.php` (`{clase_id, token, tipo}`). La pantalla del alumno
muestra un badge "Registro de entrada/salida" y el mensaje de éxito se adapta.

## Previsualización sin backend

Abrí cualquier archivo `*.html` directamente en el navegador. Traen **datos de ejemplo
hardcodeados** y los flujos funcionan en modo demo:

- **Login** → redirige al panel según el rol elegido.
- **Generar QR** → el código rota cada 30s con fade; el contador de presentes sube solo (simulado).
- **Escanear (alumno)** → pide acceso a la cámara y lee el QR; en desktop usá "Simular escaneo (demo)".
- **Exportar / Enviar** → muestran un toast (sin endpoint real conectado).

## Cómo pasar cada `.html` a `.php`

Cuando armes el backend, copiá cada `.html` a `.php` y reemplazá los **datos
hardcodeados** por tus consultas (un `foreach` sobre lo que traigas de MySQL).
Los enlaces y acciones que apuntan al servidor están marcados en el HTML con
atributos `data-*` y en los `fetch()` de los `.js` — cambiá esas URLs a tus `.php`
reales. El contrato completo de endpoints está en la sección siguiente.

## Backend (lo hacés vos, a mano)

Este proyecto es **solo frontend**. No hay PHP: lo vas a escribir vos. Esta sección es
el **contrato** que el frontend ya espera, para que sepas qué endpoints crear y qué
deben devolver. Los puntos de conexión están marcados en el HTML con `data-*` y en los
`fetch()` de los `.js`.

**Endpoints que el frontend invoca** (creá estos `.php` cuando armes el backend):

| URL que llama el front | Método | Cuerpo / Query | Respuesta esperada |
|---|---|---|---|
| `login.php` | POST | `legajo`, `password` | Inicia sesión y redirige según el rol (deducí el rol del legajo) |
| `logout.php` | GET | — | Cierra sesión → `index.php` |
| `api/token.php` | GET | `clase_id` | `{ "token": "..." }` (token corto que rota el QR) |
| `api/registrar.php` | POST | `{clase_id, token, tipo}` | `{ "ok":true, "hora":"HH:MM" }` o error `{message}` |
| `api/presentes.php` | GET | `clase_id`, `tipo` | `{ presentes, total, ultimos:[{nombre,iniciales,hora}] }` |
| `api/finalizar.php` | POST | `{clase_id}` | `{ "ok":true }` |
| `api/crear_materia.php` | POST | `{nombre,codigo,curso,modalidad,profesor_id?}` | `{ "ok":true, "materia":{...} }` |
| `api/crear_usuario.php` | POST | `{tipo,nombre,apellido,legajo,...}` | `{ "ok":true, "id":N }` |
| `api/importar_alumnos.php` | POST | archivo CSV (multipart) | `{ ok, creados, errores:[] }` |
| `api/toggle_usuario.php` | POST | `{usuario_id, activo}` | `{ "ok":true }` |
| `api/exportar.php` | GET | `clase_id` | archivo descargable (`.xls`/`.csv`) |
| `api/enviar_secretaria.php` | POST | `{clase_id}` | `{ "ok":true }` |

**Contrato del helper `fetch`:** `assets/js/utils.js` → `App.api(url, opts)` manda/recibe
JSON y, si la respuesta no es `2xx`, lee `{ "message": "..." }` para mostrar el error.
Mantené ese formato de error en tus respuestas PHP.

**Login por legajo:** en el demo el rol se deduce del primer dígito
(`2`=alumno, `1`=profesor, `3`=secretaría, `9`=admin) — ver el script de `index.html`.
Replicá esa lógica en tu `login.php`.

**Tablas sugeridas:** `usuarios`, `materias`, `clases`, `inscripciones`, `qr_tokens`,
`asistencias`, `reportes`.

## Diseño

- **Paleta:** azul institucional `#1a2744`, fondo blanco roto `#f5f6fa`, acento celeste `#38bdf8`.
  Estados: verde = presente, rojo = ausente, gris = pendiente, ámbar = en curso.
- **Tipografía:** Plus Jakarta Sans (Google Fonts).
- **Íconos:** Font Awesome 6.5 (CDN).
- **Responsive:** sidebar fija en desktop, hamburger en mobile; el escáner del alumno está
  optimizado para celular.

## CDNs usados

- QRCode.js (genera el QR del profesor) — `cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js`
- jsQR (lee el QR con la cámara del alumno) — `cdnjs.cloudflare.com/ajax/libs/jsQR/1.4.0/jsQR.min.js`
- Font Awesome 6.5 — `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css`
- Google Fonts — Plus Jakarta Sans
