# local_educaaragon — Guía para Agentes de Código

> Plugin Local de Moodle para la edición de materiales educativos (Educa Aragón).
> Desarrollado por 3iPunt <https://www.tresipunt.com/>.

---

## 1. Visión general del proyecto

Este plugin transforma contenidos dinámicos de Moodle (SCORM e IMSCP) en recursos HTML editables, generando además una versión imprimible de cada recurso. Proporciona un sistema de control de versiones ligero que permite a los editores modificar el HTML y la tabla de contenidos (TOC) de los materiales, así como aplicar las versiones editadas para que los estudiantes las visualicen.

El plugin se instala como cualquier otro plugin Local de Moodle, en la ruta `/local/educaaragon/`.

---

## 2. Stack tecnológico y arquitectura de ejecución

- **Backend:** PHP 7.4+ siguiendo la arquitectura estándar de Moodle.
- **Frontend:** JavaScript AMD (RequireJS), jQuery, Mustache (templates del core de Moodle).
- **Base de datos:** Tablas propias gestionadas mediante XMLDB y la API `persistent` de Moodle.
- **Repositorio de archivos:** Requiere un repositorio de tipo **Sistema de archivos (filesystem)** configurado en Moodle. Los contenidos HTML de los cursos deben almacenarse en `moodledata/repository/<nombre_repo>/editions/<shortname_curso>/`.
- **Procesamiento periódico:** Tarea programada de Moodle (`local_educaaragon\task\transform_dynamic_content`) que se ejecuta por defecto todos los días a las 03:00 h.
- **Eventos:** Observadores del core (`course_module_deleted`, `course_deleted`) para limpieza de datos.

### Requisitos de Moodle
- Moodle ≥ 4.1 (`2022112811`)
- Capacidad de ejecución del cron de Moodle (recomendado cada 30 s – 1 min)

---

## 3. Estructura de directorios y organización del código

```
local/educaaragon/
├── version.php              # Versión del plugin y dependencias
├── lib.php                  # Funciones de ayuda globales (remove_accents, copy_folder, etc.)
├── settings.php             # Páginas de administración y configuración
├── editables.php            # Listado de recursos editables de un curso
├── editresource.php         # Editor de contenido HTML de una versión
├── editresourcetoc.php      # Editor de la tabla de contenidos (TOC)
├── processedcourses.php     # Panel de cursos procesados
├── launchtask.php           # Lanzador manual de la tarea de transformación
├── registereditions.php     # Registro de ediciones realizadas
├── resourcelinks.php        # Informe de enlaces de una versión
├── db/
│   ├── install.xml          # Esquema de base de datos (4 tablas)
│   ├── upgrade.php          # Actualizaciones del esquema
│   ├── access.php           # Capacidades del plugin
│   ├── services.php         # Servicios web (external functions)
│   ├── events.php           # Observadores de eventos
│   └── tasks.php            # Definición de la tarea programada
├── classes/
│   ├── processcourse.php              # Lógica principal de transformación SCORM/IMSCP → HTML
│   ├── manage_editable_resource.php   # Gestión de versiones, archivos y aplicación
│   ├── manage_logs.php                # Helper de logs y persistencia
│   ├── eventobservers.php             # Limpieza de datos ante eliminaciones
│   ├── educa_editables.php            # Clase persistente (tabla local_educa_editables)
│   ├── educa_edited.php               # Clase persistente (tabla local_educa_edited)
│   ├── educa_processedcourses.php     # Clase persistente (tabla local_educa_processedcourses)
│   ├── educa_resource_links.php       # Clase persistente (tabla local_educa_resource_links)
│   ├── editables_table.php            # Tabla Moodle para listado de editables
│   ├── processedcourses_table.php     # Tabla Moodle para cursos procesados
│   ├── registereditions_table.php     # Tabla Moodle para registro de ediciones
│   ├── resourcelinks_table.php        # Tabla Moodle para informe de enlaces
│   ├── task/
│   │   └── transform_dynamic_content.php   # Tarea programada
│   ├── external/                      # Servicios web AJAX
│   │   ├── applyversion_external.php
│   │   ├── createversion_external.php
│   │   ├── deleteversion_external.php
│   │   ├── processedtable_external.php
│   │   ├── process_resource_links.php
│   │   ├── reprocessing_external.php
│   │   ├── savechanges_external.php
│   │   └── savetocchanges_external.php
│   └── output/                        # Renderables y templates
│       ├── renderer.php
│       ├── editables_page.php
│       ├── editresource_page.php
│       ├── editresourcetoc_page.php
│       ├── processedcourses_page.php
│       ├── registereditions_page.php
│       └── resourcelinks_page.php
├── amd/src/                 # Módulos JavaScript AMD (fuente)
│   ├── editresource.js      # UI de edición de contenido y versiones
│   ├── edittoc.js           # UI de edición de TOC (drag & drop)
│   └── processedcourses_page.js  # UI del panel de cursos procesados
├── amd/build/               # Módulos minificados (compilados por Moodle)
├── templates/               # Plantillas Mustache
│   ├── editables.mustache
│   ├── editresource.mustache
│   ├── editresourcetoc.mustache
│   ├── processedcourses.mustache
│   ├── processedcourses_table.mustache
│   ├── registereditions.mustache
│   ├── resourcelinks.mustache
│   ├── version_control.mustache
│   └── version_loaded.mustache
└── lang/
    ├── es/local_educaaragon.php   # Cadenas en español (idioma principal)
    └── en/local_educaaragon.php   # Cadenas en inglés
```

---

## 4. Modelo de datos

El plugin define **4 tablas** en `db/install.xml`:

| Tabla | Propósito |
|-------|-----------|
| `local_educa_processedcourses` | Registro de cursos procesados por la tarea programada. Campos: `courseid`, `processed` (bool), `message`. |
| `local_educa_editables` | Recursos editables y versiones imprimibles generados por curso. Campos: `courseid`, `resourceid`, `type` (`editable` / `printable`), `relatedcmid`, `version`. |
| `local_educa_edited` | Auditoría de todas las acciones realizadas sobre los recursos (crear versión, guardar cambios, aplicar versión, etc.). |
| `local_educa_resource_links` | Resultados del análisis de enlaces: estado (`link_active`, `link_broken`, `link_fixed`, …), URL, archivo donde se encontró, etc. |

Cada tabla tiene su clase `persistent` correspondiente en `classes/educa_*.php`.

---

## 5. Flujo principal de funcionamiento

### 5.1 Transformación inicial (tarea programada)
1. La tarea `transform_dynamic_content` recorre los cursos de la categoría configurada (o todos).
2. Por cada curso no procesado, busca en el repositorio filesystem una carpeta cuyo nombre coincida con el `shortname` del curso.
3. Identifica los módulos SCORM e IMSCP del curso (excepto sección 0).
4. Crea, para cada contenido, dos recursos de tipo `mod_resource`:
   - **Editable:** recurso HTML estándar con todos los archivos del repositorio.
   - **Imprimible:** recurso HTML donde se unifican todos los archivos `.html` en un único `index.html`, eliminando navegación y añadiendo CSS de impresión.
5. Oculta los módulos SCORM/IMSCP originales y registra todo en `local_educa_editables`.

### 5.2 Edición de contenidos
1. El usuario accede a la página `editables.php` desde el menú del curso (solo si tiene la capacidad `local/educaaragon:editresources`).
2. Desde `editresource.php` puede:
   - Crear nuevas versiones a partir de otra existente.
   - Editar el HTML de cualquier archivo de la versión mediante el editor Atto.
   - Editar la tabla de contenidos (`editresourcetoc.php`) con drag & drop.
   - Guardar cambios en la versión.
   - Aplicar una versión para que sea la visible por los estudiantes.
   - Procesar y revisar enlaces rotos (`resourcelinks.php`).

### 5.3 Gestión de versiones en el repositorio
- Dentro del repositorio filesystem, cada recurso editable tiene una carpeta en:
  `editions/<shortname_curso>/<resourceid>/`
- Dentro de esa carpeta se crea la subcarpeta `original` (copia del contenido del módulo resource) y las carpetas de cada versión editada.
- La versión `original` no se puede editar ni eliminar.

---

## 6. Convenciones de código

- **Namespaces:**
  - Clases generales: `local_educaaragon\`
  - Servicios externos: `local_educaaragon\external\`
  - Renderables: `local_educaaragon\output\`
  - Tareas: `local_educaaragon\task\`
- **Archivos PHP:** Siempre empiezan con `defined('MOODLE_INTERNAL') || die();`.
- **Documentación:** PHPDoc en inglés siguiendo el estándar de Moodle.
- **Strings:** Todas las cadenas visibles para el usuario deben definirse en `lang/es/local_educaaragon.php` (idioma principal del proyecto) y en `lang/en/local_educaaragon.php`.
- **Limpieza de strings:** Existe una función propia `clean_string()` en `lib.php` que elimina acentos, espacios y caracteres especiales para nombres de versión.
- **Manipulación HTML:** Se utiliza extensivamente `DOMDocument` de PHP; es habitual ver `libxml_use_internal_errors(true)` para suprimir warnings de HTML malformado.
- **Logging:** La tarea programada usa `mtrace()` para imprimir por consola durante la ejecución del cron.

---

## 7. Build y despliegue

No existe un proceso de build personalizado (no hay `package.json`, `composer.json`, `Gruntfile`, etc.).

- **AMD:** Los archivos de `amd/src/` deben compilarse a `amd/build/` usando las herramientas estándar de Moodle (`grunt amd` desde la raíz de Moodle, o el proceso de desarrollo habitual del core).
- **Instalación:** Copiar la carpeta del plugin a `/local/educaaragon/` y visitar la administración de Moodle para completar la instalación (creación de tablas, servicios, eventos, tarea programada y capacidades).
- **Configuración obligatoria tras la instalación:**
  1. Crear un repositorio de tipo *Sistema de archivos* en Moodle.
  2. En `Administración del sitio → Cursos → Educa Aragón → Ajustes generales`:
     - Seleccionar el repositorio de contenidos.
     - Elegir si aplica a todos los cursos o a una categoría específica.
     - Activar la tarea programada cuando se desee que el cron procese los cursos automáticamente.
  3. Configurar la tarea programada en `Administración del sitio → Servidor → Tareas → Tareas Programadas`.
  4. Opcionalmente, ejecutar la transformación de forma inmediata desde `Administración del sitio → Cursos → Educa Aragón → Lanzar tarea de transformación` para todos los cursos configurados o para un curso concreto.

---

## 8. Estrategia de testing

**No se incluyen tests automatizados en el repositorio.** No hay suites de PHPUnit ni escenarios de Behat. El plugin se valida manualmente mediante:

1. Ejecución de la tarea programada y revisión de los logs del cron.
2. Verificación de la creación correcta de recursos editables e imprimibles en un curso de prueba.
3. Pruebas de creación, edición, guardado y aplicación de versiones.
4. Comprobación del informe de enlaces tras el procesado de una versión.
5. Validación de que los observadores de eventos limpian correctamente las tablas y el filesystem al eliminar cursos o módulos.

---

## 9. Consideraciones de seguridad

- **Capacidades:**
  - `local/educaaragon:manageall` — Administración global del plugin (contexto sistema).
  - `local/educaaragon:editresources` — Edición de recursos dentro de un curso (contexto curso).
- **Validación de parámetros:** Los servicios externos (`external_api`) definen explícitamente los parámetros de entrada (`PARAM_INT`, `PARAM_RAW`, etc.) y validan el contexto del curso.
- **Capacidades en servicios AJAX:** Cada función externa invoca `require_capability()` tras `validate_context()`.
- **Limpieza de salida:** Las URLs y nombres de archivo se sanitizan con funciones propias (`clean_string`, `clean_url`).
- **Filesystem:** El plugin lee y escribe directamente en el disco a través del repositorio filesystem. Presta atención a los permisos de `moodledata/repository/`.
- **Destrucción de datos:** Al desinstalar el plugin, se eliminan todas las tablas creadas y **todo el contenido** de la carpeta `editions/` del repositorio. Esta operación es irreversible.

---

## 10. Puntos de atención para modificaciones

- **No modificar la lógica de unificación de archivos imprimibles** (`unify_files()` en `classes/processcourse.php` y `classes/manage_editable_resource.php`) sin probar exhaustivamente con contenido real; depende fuertemente de la estructura HTML generada por los materiales del cliente (IDs como `siteNav`, `main`, `topPagination`, etc.).
- **El editor de TOC** (`amd/src/edittoc.js`) implementa su propia biblioteca de drag-and-drop basada en jQuery. Cualquier cambio en la estructura del DOM de la tabla de contenidos puede romper la serialización.
- **La tarea programada utiliza `phpunit_util::get_data_generator()`** para obtener el generador de instancias de `mod_resource`. Esto es inusual para código de producción; si Moodle cambia esta API interna, la tarea podría fallar.
- **Codificación de caracteres:** El proyecto maneja textos en español con tildes y eñes. Al manipular `DOMDocument`, se usan conversiones entre UTF-8 y `HTML-ENTITIES`; cualquier cambio en este flujo puede provocar problemas de codificación.
- **Carpeta temporal de procesado:** `fileprocessing/` se utiliza como ruta de trabajo intermedia para la generación de recursos imprimibles. Debe tener permisos de escritura.
