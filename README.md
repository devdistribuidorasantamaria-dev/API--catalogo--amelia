# Amelia Boutique — Backend

Laravel 13 (PHP 8.3) + PostgreSQL 16. Dos caras:

- **Panel admin** (`/admin`, Blade + Breeze con sesión) — donde se administra el catálogo.
- **API pública de sólo lectura** (`/api/*`) — la consume [`../amelia-frontend`](../amelia-frontend).

No sirve nada público: `/` redirige a `/admin`.

## Arrancar

```bash
composer install
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve            # http://localhost:8000
```

Acceso sembrado: **admin@ameliaboutique.com** / **Amelia@2026** — cámbialo antes de
producción (desde «Mi cuenta» en el panel).

El registro público está deshabilitado a propósito: las cuentas se crean por seeder o
`php artisan tinker`.

## Base de datos

Requiere las extensiones PHP `pdo_pgsql` y `gd` (redimensionado de fotos).

```sql
CREATE ROLE amelia WITH LOGIN PASSWORD '...' CREATEDB;
CREATE DATABASE amelia_boutique OWNER amelia ENCODING 'UTF8';
CREATE DATABASE amelia_boutique_test OWNER amelia ENCODING 'UTF8';  -- para los tests
```

### Esquema

| Tabla             | Notas                                                                                                       |
| ----------------- | ----------------------------------------------------------------------------------------------------------- |
| `secciones`       | Capítulos del catálogo. `orden` decide la posición.                                                          |
| `prendas`         | `tallas` es `jsonb`. `precio_desde`/`precio_hasta` permiten valor único o rango. `seccion_id` nulo = sin sección. |
| `prenda_imagenes` | `orden = 0` es la portada. Se borran en cascada con la prenda.                                                |
| `ajustes`         | Clave/valor editable desde el panel: `subtitulo`, `whatsapp_numero`, `whatsapp_mensaje`, `logo_ruta`, `logo_ancho`, `logo_alto`. |

Al borrar una sección sus prendas **no** se borran: quedan sin sección (`nullOnDelete`).

## API

`GET /api/catalogo`

```json
{
  "subtitulo": "Colección · Santo Domingo, Ecuador",
  "logo_url": "http://localhost:8000/storage/marca/ab12cd.png",
  "logo_ancho": 472,
  "logo_alto": 247,
  "whatsapp_url": "https://wa.me/593987654321?text=Hola…",
  "total_prendas": 6,
  "bloques": [
    { "seccion": null, "prendas": [] },
    { "seccion": { "id": 1, "nombre": "Vestidos", "slug": "vestidos" }, "prendas": [] }
  ]
}
```

Los bloques vienen en el orden en que se renderizan; el bloque `seccion: null` va primero y
se dibuja sin encabezado. Cada prenda trae `precio_texto` ya formateado
(`"$25.00"`, `"$25.00 – $30.00"` o `"—"`) para que el frontend no duplique esa lógica.

`GET /api/prendas/{slug}` — una prenda (404 si está oculta).

CORS sólo permite el origen de `FRONTEND_URL` y métodos de lectura.

## Logotipo de la cabecera

Se sube en **Ajustes** del panel. `App\Services\Logotipo` guarda la ruta y las dimensiones
en `ajustes` (`logo_ruta`, `logo_ancho`, `logo_alto`) y la API los expone como `logo_url`,
`logo_ancho` y `logo_alto`.

- `ImagenService::guardarLogo()` reencoda a **PNG conservando la transparencia** (el
  catálogo es negro; el JPEG de las fotos rellena en blanco y arruinaría el logo) y
  redimensiona a `config('amelia.logo_ancho_max')` (720 px, 2× del ancho de pantalla).
- Vive en `storage/app/public/marca`. Al reemplazarlo se borra el archivo anterior.
- La casilla «Quitar el logotipo» lo borra: `logo_url` vuelve a `null` y el frontend
  escribe el nombre en Cormorant. Subir un archivo manda sobre la casilla.

## Botón de contacto (WhatsApp)

Se configura en **Ajustes** del panel. `App\Services\ContactoWhatsapp` arma el enlace
`https://wa.me/<numero>?text=<mensaje>` y la API lo expone como `whatsapp_url`.

- El número se guarda sólo con dígitos, en formato internacional (`593987654321`).
  `normalizarNumero()` acepta que se escriba con `+`, espacios o guiones.
- Sin número configurado, `whatsapp_url` es `null` y el frontend esconde el botón.

## Fotos

Se suben desde el panel: se reencodan a JPEG y se redimensionan a
`config('amelia.imagen_ancho_max')` (1400 px) con GD, respetando la orientación EXIF y
descartando el resto de los metadatos. Viven en el disco `public`
(`storage/app/public/prendas`), servidas por `/storage/**`.

En el formulario, las fotos se reordenan con ↑ ↓ (la primera es la portada) y el orden
guardado se normaliza a 0..n para que no queden huecos tras borrados.

## Invalidación de caché del frontend

Tras cada cambio en el panel, `App\Services\Revalidador` hace
`POST {NEXT_REVALIDATE_URL}` con la cabecera `X-Revalidate-Secret`.
Si el frontend no responde se registra un warning y el guardado continúa igual
(el catálogo se actualizará cuando expire su caché, dentro de la hora).

Si agregas un controlador que escriba en el catálogo, inyecta `Revalidador` y llama a
`avisar()`; si no, los cambios tardarán hasta una hora en verse.

## Tests

```bash
php artisan test
```

Corren contra `amelia_boutique_test` en Postgres: el esquema usa `jsonb` y no es
portable a sqlite.
