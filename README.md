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
| `eventos_analitica` | Eventos anónimos del catálogo: `tipo`, `prenda_id` (nulo en las visitas), `visitante_hash`, `creado_en`. |

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

`POST /api/eventos` — ingesta de analítica, la única escritura de la API. Ver más abajo.

CORS sólo permite el origen de `FRONTEND_URL`; los métodos son de lectura más el `POST`
de analítica.

## Analítica

El catálogo registra tres eventos anónimos: `visita` (al abrir la portada), `agregar` y
`consultar` (los dos botones de cada prenda). Se guardan por separado aunque el panel los
sume como «selecciones», para poder desglosarlos más adelante sin perder el histórico.

### Ingesta

```
POST /api/eventos
tipo=visita
tipo=agregar&prenda_id=12
tipo=consultar&prenda_id=12
```

Responde **`204` siempre**, tanto si guarda como si descarta: el frontend dispara y se
olvida. El cuerpo va en `application/x-www-form-urlencoded` porque es un tipo «simple»
para CORS y evita un `OPTIONS` de preflight por cada evento. Lleva `throttle:60,1`.

Valida que el tipo exista, que `agregar`/`consultar` traigan una prenda real y que
`visita` **no** traiga ninguna.

### Privacidad

No se guarda IP, user-agent, cookie ni referente. `App\Services\RegistroAnalitica`:

- **Descarta bots** por user-agent (buscadores, previsualizadores de enlaces, navegadores
  automatizados, clientes de consola). Efecto colateral útil: Lighthouse y PageSpeed no
  ensucian las cifras. Los agentes que podrían confundirse con un navegador real se exigen
  con la barra del producto (`whatsapp/`, `java/`) para no descartar a quien abre el
  catálogo desde el navegador embebido de esas apps.
- **`visitante_hash`** es `hash_hmac('sha256', ip|user-agent|fecha, APP_KEY)`: irreversible
  y con sal diaria, así que no permite volver a la IP ni seguir a nadie de un día para
  otro. Sólo sirve para separar visitas únicas de recargas dentro del mismo día.

### Panel

`/admin/analitica`, con rango de 7, 30 o 90 días en la URL (`?dias=30`). Muestra visitas
por día, selecciones por día, tasa de interés, ranking de las 10 prendas más seleccionadas
y el reparto por tipo y por sección.

Las gráficas se dibujan con CSS y SVG en línea, sin librería: el panel es monocromo y las
series se distinguen por tono y textura (sólido = agregar, tramado = consultar), no por
color. Están en `resources/views/components/analitica/`.

Al borrar una prenda sus eventos **no** se borran: quedan con `prenda_id` nulo, así que
salen del ranking pero siguen contando en los totales del periodo.

### Datos de prueba

```bash
php artisan db:seed --class=AnaliticaDemoSeeder
```

90 días de visitas y selecciones simuladas, con tendencia creciente, curva semanal y unas
pocas prendas acaparando el ranking. Es determinista (semilla fija): dos ejecuciones dan
el mismo tablero. **Borra los eventos que haya** antes de generar los nuevos, se niega a
correr en producción y pide confirmación si ya hay datos (`--force` se la salta).

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
