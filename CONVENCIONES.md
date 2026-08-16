# Amelia Boutique — Backend · convenciones

Laravel 13 + PHP 8.3 + PostgreSQL 16. Panel admin en Blade (`/admin`) + API pública de
sólo lectura (`/api/*`) que consume `../amelia-frontend`.

Lee `README.md` para el arranque, el esquema y la forma de la API.

## Reglas del proyecto

- **Español** en textos de interfaz, comentarios, nombres de tablas, columnas, modelos y
  rutas (`prendas`, `secciones`, `Prenda`, `Seccion`, `activa`, `orden`). Las APIs de
  Laravel quedan en inglés.
- `Seccion` fija `$table = 'secciones'` a mano — Eloquent no deduce ese plural.
- La API es **sólo lectura** para el catálogo: el panel usa sesión (Breeze). Única
  excepción, `POST /api/eventos` (analítica): no lee nada, responde siempre `204`, lleva
  `throttle:60,1` y sólo guarda tipo + prenda + marca de tiempo. Cualquier otro endpoint
  de escritura va al panel, no aquí.
- Analítica: nada de datos personales. `App\Services\RegistroAnalitica` descarta bots por
  user-agent y guarda una huella con sal diaria (`visitante_hash`), nunca la IP ni el
  user-agent en claro. Si agregas un evento nuevo, añádelo a `App\Enums\TipoEvento`.
- Registro público deshabilitado a propósito (`routes/auth.php`). Cuentas por seeder/tinker.
- Precios: `precio_desde` + `precio_hasta` nullable. Formatea siempre con
  `Prenda::precioTexto()`, no armes la cadena en la vista ni en el frontend.
- Fotos: pasan por `App\Services\ImagenService` (reencoda a JPEG, redimensiona, respeta
  EXIF). Nunca guardes el `UploadedFile` directo.
- Todo cambio del catálogo debe llamar a `App\Services\Revalidador::avisar()` para que el
  frontend purgue su caché. Si agregas un controlador de escritura, inyéctalo.
- Estilos del panel: paleta y primitivas (`.a-btn`, `.a-input`, `.a-label`, `.a-hint`,
  `.a-card`, `.a-check`, `.a-eyebrow`, `.a-title`) en `resources/css/app.css`.
  Sin clases `gray-*`/`indigo-*` de Breeze — el panel es oscuro como el catálogo.
  Tras tocar CSS o Blade: `npm run build` y `php artisan view:clear`.
- Evita glifos exóticos (`＋` fullwidth y similares) en las vistas: no están en todas las
  fuentes. Usa ASCII o flechas comunes.

## Tests

`php artisan test` — corren contra Postgres (`amelia_boutique_test`), no sqlite: el
esquema usa `jsonb`.
