# fp-virtual-plugin-edicion-materiales

- [fp-virtual-plugin-edicion-materiales](#fp-virtual-plugin-edicion-materiales)
  - [Plugin de Moodle para la edición de materiales del ministerio](#plugin-de-moodle-para-la-edición-de-materiales-del-ministerio)
    - [Requisitos](#requisitos)
- [Repositorio](#repositorio)
    - [Contenido del repositorio](#contenido-del-repositorio)
    - [Contenido fuente](#contenido-fuente)
    - [Carpeta `editions/` (versiones editadas)](#carpeta-editions-versiones-editadas)
    - [Carpeta temporal de procesado (`fileprocessing/`)](#carpeta-temporal-de-procesado-fileprocessing)
  - [Configuración](#configuración)
    - [Tarea Programada](#tarea-programada)
    - [Ejecución manual de tareas](#ejecución-manual-de-tareas)
      - [Funcionamiento del proceso de importación](#funcionamiento-del-proceso-de-importación)
      - [Migración de versiones entre instalaciones (CLI)](#migración-de-versiones-entre-instalaciones-cli)
- [Desinstalación](#desinstalación)

## Plugin de Moodle para la edición de materiales del ministerio

El plugin se instala como cualquier otro plugin Local, añadiendo los archivos dentro de la carpeta /local/educaaragon/ y pasando por la administración.

Durante la instalación, el plugin creará en la base de datos las tablas, servicios, eventos, tarea programada y capacidades que necesita para funcionar.

### Requisitos

*   Moodle ≥ 4.1 (`2022112811`)
*   PHP 7.4 o superior (el plugin es compatible con PHP 8.2)
*   Un repositorio de tipo **Sistema de archivos** configurado (ver siguiente apartado)
*   Capacidad de ejecución del cron de Moodle (recomendado cada 30 s – 1 min)

Repositorio
===========

Para que el plugin funcione, es necesario que se cree un repositorio dentro de Moodle del tipo “Sistema de archivos”, con cualquier nombre que permita identificarlo posteriormente.

Los pasos a seguir son los siguientes:

*   Crear una carpeta en **“moodledata/repository”** con el nombre del repositorio. Dentro de ella deben existir los contenidos fuente de los cursos y, opcionalmente desde el inicio, la carpeta `editions` (la creará y gestionará el plugin para las versiones editadas). La ubicación exacta de los contenidos fuente se configura en el ajuste **Carpeta de contenidos fuente**.
    
*   Dentro de la administración de Moodle, ir a **Administración del sitio→ Extensiones → Repositorios → Gestionar Repositorios → Sistema de archivos**, debe estar marcado como “Activado y visible”
    
*   Pinchando en **“configuración” del Sistema de archivos**, podremos **crear una nueva instancia de repositorio**, donde podremos **asociar la carpeta raíz que hemos creado en “moodledata/repository”**
    
*   La configuración deberá quedar así (con el nombre que deseemos, y con la carpeta correspondiente seleccionada)

### Contenido del repositorio

Dentro del repositorio que acabamos de crear, los contenidos se organizan en dos carpetas principales:

### Contenido fuente

Aquí se colocan los contenidos originales que utilizará la tarea de transformación para dar de alta los recursos editables. La ubicación de esta carpeta se indica en el ajuste **Carpeta de contenidos fuente**:

*   Si se deja **vacío**, la tarea buscará las carpetas de los cursos directamente en la raíz del repositorio:
    ```
    <raíz_repo>/<shortname_curso>/<orden>/index.html
    ```

*   Si se escribe un nombre de carpeta (por ejemplo, `recursos-editables`), la tarea buscará dentro de esa subcarpeta:
    ```
    <raíz_repo>/recursos-editables/<shortname_curso>/<orden>/index.html
    ```

En cualquier caso, dentro de la carpeta de cada curso debe existir **una subcarpeta por cada recurso que se vaya a generar**, recomendable que esté nombrada con `01`, `02`, `03`… según el orden de aparición del recurso en el curso, para facilitar la ordenación.

**Dentro de cada carpeta de un recurso deberán estar todos los ficheros necesarios para que el contenido funcione correctamente, así como un fichero `index.html`** que será el que sirva de disparador del contenido. Si este fichero no existe, el recurso no se generará.

### Carpeta `editions/` (versiones editadas)

Esta carpeta la crea y gestiona el propio plugin. Su estructura es:

```
editions/<shortname_curso>/<resourceid>/
```

Dentro de cada `<resourceid>` se encuentran las versiones del recurso:

*   `original/`: copia del contenido del recurso editable tal como se generó en la primera transformación. No debe editarse ni eliminarse.
*   `v1_2025_2026/`, `v2_.../`, etc.: versiones creadas posteriormente desde el panel de edición.

Si ya existe `editions/<shortname_curso>/` y el curso ya tiene recursos editables registrados por el plugin, la tarea de transformación no volverá a crear los recursos desde el contenido fuente, sino que reconocerá las versiones ya existentes. Si no hay registros de recursos editables, se tratará como un procesado inicial.

### Carpeta temporal de procesado (`fileprocessing/`)

La generación de los recursos imprimibles utiliza como ruta de trabajo intermedia la carpeta `local/educaaragon/fileprocessing/` dentro de la instalación de Moodle. Debe tener permisos de escritura para el usuario del servidor web (el plugin la crea automáticamente si no existe al procesar un curso).

> **En entornos contenerizados (Docker/Podman):** esta carpeta vive dentro del contenedor y normalmente **no está montada en el host**, por lo que no la verás desde el sistema de archivos del servidor. Si necesitas inspeccionarla, hazlo desde dentro del contenedor.


## Configuración

Una vez instalado el plugin, para su configuración tendremos que ir a **Administración del sitio → Cursos → Educa Aragón → Ajustes generales**

Aquí podremos activar o desactivar el procesamiento de tareas.

Al activarla, se nos mostrarán distintas opciones:

*   **Activar tarea programada para transformar recursos:** activa o desactiva el procesamiento de cursos por la tarea programada (aunque la tarea se ejecute, si esta opción está desmarcada no se procesará ningún curso).
    
*   **Repositorio de contenidos:** selección del repositorio donde están contenidos todos los recursos exportados.
    
*   **Carpeta de contenidos fuente:** nombre de la carpeta dentro del repositorio donde están los contenidos originales de los cursos. Déjela vacía si las carpetas de los cursos están directamente en la raíz del repositorio. Indique `recursos-editables` (o el nombre correspondiente) si los contenidos están en una subcarpeta. Las versiones editadas siempre se guardan en `editions/`.
    
*   **Aplicar a todos los cursos:** si se marca esta casilla, todos los cursos de la plataforma serán procesados. Si se desmarca, aparecerá el selector de categorías de curso.
    
*   **Categoría:** el proceso de cursos sólo se hará sobre los cursos que pertenezcan a esta categoría (incluyendo los cursos de las subcategorías)
    

### Tarea Programada

Para configurar la tarea programada del plugin hay que ir a **Administración del sitio → Servidor → Tareas → Tareas Programadas** y en el listado buscar **"Transformar contenidos dinámicos"** (clase `local_educaaragon\task\transform_dynamic_content`)

Desde este panel podrá **configurar la tarea de la misma forma que cualquier otra tarea de moodle, o leer los registros que se han generado durante su ejecución.**

Documentación oficial para configurar tareas programadas: [https://docs.moodle.org/4x/en/Scheduled\_tasks](https://docs.moodle.org/4x/en/Scheduled_tasks)

Debido a la posible duración de la tarea y a que crea nuevos contenidos en el curso para los estudiantes finales, se recomienda configurar la tarea para que se ejecute una vez al día en horario con poca concurrencia en la plataforma (por defecto, se crea configurada para que pase todos los días a las 3 a.m)

Independientemente del periodo de ejecución que se programe para esta tarea, **se recomienda configurar el cron para que se ejecute cada 30 segundos o cada minuto**, ya que este plugin utiliza eventos del core para realizar ciertos procesos, y sólo se dispararán durante la ejecución del cron.


### Ejecución manual de tareas

Además de la ejecución programada, las tareas pueden lanzarse de forma manual cuando sea necesario. Existen tres métodos:

**Desde la página de ejecución manual del plugin:**

En **Administración del sitio → Cursos → Educa Aragón → Ejecución manual de tareas** podrá ejecutar de forma inmediata cualquiera de las dos tareas del plugin:

*   **Generación de materiales editables** (`transform_dynamic_content`): crea los recursos editables e imprimibles de los cursos a partir de sus contenidos dinámicos (SCORM/IMSCP). Ámbitos:
    *   *Procesar todos los cursos*: todos los cursos no procesados según la configuración actual.
    *   *Procesar un curso concreto*: un curso del listado (dispone de buscador). Si ya fue procesado, pedirá confirmación, ya que el reprocesado elimina los recursos generados anteriormente.
    *   *Procesar un centro completo*: los cursos no procesados cuyo código de centro (primer tramo del nombre corto, p. ej. `50020125`) coincida con el indicado.
*   **Importación de versiones de materiales** (`edition_versions_migrator`): copia las versiones editadas guardadas bajo identificadores antiguos hacia los recursos actuales de los cursos (misma lógica que el script CLI `migrate_edition_versions.php`). Se puede lanzar a nivel global, de curso concreto o de centro completo, y dispone de opciones: versión a aplicar tras importar, copia de la carpeta `original` y modo simulación (*dry-run*).

    > **Requisito:** la importación de versiones necesita que la generación de materiales editables se haya ejecutado correctamente con anterioridad sobre los cursos afectados, ya que empareja las versiones con los recursos existentes. La propia generación ya importa automáticamente las versiones al procesar un curso por primera vez (ver paso 7 de *Funcionamiento del proceso de importación*).

Ambas ejecuciones muestran el resultado en pantalla y generan un documento de log en `<raíz_repo>/editions/_logs/`: `generacion_<ámbito>_<fecha>.log` o `importacion_<ámbito>_<fecha>[_dryrun].log`.

**Desde la interfaz web:**

En **Administración del sitio → Servidor → Tareas → Tareas Programadas**, buscar **"Transformar contenidos dinámicos"** y hacer clic en el botón **"Ejecutar ahora"**.

**Desde la línea de comandos (CLI):**

Desde la raíz de la instalación de Moodle, ejecutar:

```bash
php admin/cli/scheduled_task.php --execute="\local_educaaragon\task\transform_dynamic_content"
```

También es posible verificar que Moodle reconoce la tarea listándola con:

```bash
php admin/cli/scheduled_task.php --list | grep educaaragon
```

**En entornos contenerizados (Docker/Podman):**

Si Moodle se ejecuta dentro de un contenedor, accede al contenedor y lanza la tarea desde su interior:

```bash
# Ejemplo con Docker
docker exec -it <nombre_contenedor_moodle> php /var/www/html/admin/cli/scheduled_task.php --execute="\local_educaaragon\task\transform_dynamic_content"
```

```bash
# Ejemplo con docker-compose
docker compose exec <servicio_moodle> php /var/www/html/admin/cli/scheduled_task.php --execute="\local_educaaragon\task\transform_dynamic_content"
```

> Asegúrate de reemplazar `<nombre_contenedor_moodle>` o `<servicio_moodle>` por el nombre real de tu contenedor/servicio, y de que la ruta `/var/www/html` corresponda al directorio donde esté instalado Moodle dentro del contenedor.

#### Funcionamiento del proceso de importación

Cuando la tarea **"Transformar contenidos dinámicos"** procesa un curso, realiza los siguientes pasos:

**1. Arranque y selección de cursos**

1.  Comprueba el ajuste *Activar tarea programada para transformar recursos*; si está desactivado, no se procesa nada (salvo que se fuerce la ejecución desde el lanzador manual).
2.  Obtiene los cursos a procesar: todos los de la plataforma si *Aplicar a todos los cursos* está marcado, o los cursos visibles de la *Categoría* configurada (incluyendo sus subcategorías).
3.  Descarta los cursos que ya consten como procesados en la tabla `local_educa_processedcourses`.
4.  Inicializa el repositorio de archivos, el generador de instancias de `mod_resource` y el contexto del usuario administrador.

**2. Preparación del curso**

1.  Registra el curso en `local_educa_processedcourses` (estado pendiente).
2.  Localiza la carpeta del curso en el repositorio según el ajuste *Carpeta de contenidos fuente*: `<raíz_repo>/<shortname_curso>` si está vacío, o `<raíz_repo>/<carpeta_fuente>/<shortname_curso>` si tiene valor. Si no la encuentra, marca el curso con el error `no_associated_folder` y pasa al siguiente.
3.  Decide el modo de procesado:
    *   Si existe `editions/<shortname_curso>/` **y** el curso ya tiene recursos editables registrados en `local_educa_editables` → **modo reconocimiento** (paso 5).
    *   En caso contrario → **procesado inicial** (pasos 3, 4 y 7).

**3. Procesado inicial: transformación**

1.  Lista las subcarpetas de contenido del curso (`01`, `02`, …) y los módulos **SCORM/IMSCP** del curso (los de la sección 0 se ignoran).
2.  Si el número de módulos SCORM/IMSCP coincide con el número de carpetas, intenta **asociar** cada módulo con su carpeta comparando los dígitos del nombre del módulo (`01`, `02`…) con el nombre de la carpeta.
3.  Si las asociaciones no son posibles, o si no hay contenidos dinámicos, genera los recursos **sin asociación**.

**4. Generación de los recursos** (por cada recurso)

*Recurso editable:*

1.  Crea un `mod_resource` HTML con el mismo nombre y en la misma sección que el SCORM original. En modo sin asociación se nombra `<orden> - <título>` (el título se extrae de la etiqueta `<title>` del `index.html`) y se coloca en una sección nueva llamada **"Materiales Ministerio - Editables"**.
2.  Copia todos los archivos de la carpeta del repositorio al recurso, con `index.html` como archivo principal.
3.  Oculta el módulo original y sitúa el nuevo recurso justo después de él.

*Recurso imprimible:*

1.  Copia los archivos a la carpeta temporal `fileprocessing/` y los unifica en un único `index.html`: elimina la navegación (`siteNav` y paginaciones superior e inferior), inserta el contenido de cada página enlazada dentro del documento principal, y añade reglas CSS de impresión (saltos de página) junto con metadatos de no-caché.
2.  Crea un segundo `mod_resource` llamado `<nombre> (imprimible)` con el resultado unificado, colocado justo después del recurso editable.

*Versión original:*

1.  Crea en el repositorio la carpeta `editions/<shortname_curso>/<resourceid>/original/` con una copia de los archivos del recurso editable (si no existía ya).
2.  Registra ambos recursos (editable e imprimible) en `local_educa_editables` con versión `original`.

**5. Modo reconocimiento (cursos ya procesados)**

La tarea no vuelve a crear recursos: únicamente recorre los recursos editables registrados del curso y se asegura de que cada uno disponga de su carpeta `original` en `editions/<shortname_curso>/<resourceid>/`.

**6. Análisis de enlaces**

Tras transformar los contenidos, para cada recurso editable del curso:

1.  Borra los resultados anteriores en `local_educa_resource_links`.
2.  Analiza los archivos HTML de la versión `original` en busca de enlaces y registra su estado (activo, roto, corregido…) en dicha tabla.

**7. Importación de versiones editadas (solo en el procesado inicial)**

Solo si el curso se ha procesado por primera vez (pasos 3 y 4) y existe `editions/<shortname_curso>/` en el repositorio, la tarea migra automáticamente las versiones editadas que pudieran existir bajo **ids antiguos** hacia los ids de los recursos recién creados (misma lógica que el script CLI `migrate_edition_versions.php`, ver siguiente apartado). Se ejecuta **después** del análisis de enlaces, de modo que este solo analiza las versiones `original`.

*   El emparejamiento entre ids antiguos y nuevos es posicional (ambas listas ordenadas numéricamente).
*   Las versiones se **copian** (no se mueven) y **no se aplican**: los alumnos siguen viendo el contenido `original` hasta que un editor aplique una versión desde el panel de edición.
*   Si no hay carpetas de ids antiguos en `editions/`, este paso no hace nada.

**8. Resultado del procesado**

El curso queda registrado en `local_educa_processedcourses` con uno de estos mensajes:

*   `correctly_processed`: procesado correctamente (modo asociación o reconocimiento).
*   `correctly_processed_needassociation`: procesado correctamente, pero los recursos se generaron sin asociación; conviene revisar la correspondencia entre recursos y carpetas.
*   `no_associated_folder`: no se encontró la carpeta del curso en el repositorio.
*   Mensaje de error, si el procesado falló por cualquier otra causa.


#### Migración de versiones entre instalaciones (CLI)

Si se reinstala la plataforma desde cero (base de datos limpia) pero se conserva la carpeta `editions/` de una instalación anterior, los recursos se vuelven a crear con **ids nuevos** y las versiones editadas quedan "huérfanas" bajo los ids antiguos. El script `cli/migrate_edition_versions.php` migra esas versiones a los ids nuevos para que vuelvan a aparecer en el panel de edición.

> **Nota:** la tarea de transformación ya ejecuta esta migración automáticamente al procesar un curso por primera vez (ver paso 7 de *Funcionamiento del proceso de importación*). El script CLI sigue siendo útil para comprobar el emparejamiento por adelantado (`--dry-run --verbose`), migrar de forma controlada curso a curso (`--course`) o centro a centro (`--center`), o aplicar una versión migrada con `--apply-version`.

**Flujo completo (ejecución manual):**

1.  Procesar el curso con la tarea de transformación (ver *Ejecución manual de la tarea*). Se crean los recursos editables/imprimibles y sus carpetas `original` con los ids nuevos, y al final del procesado la tarea migra automáticamente las versiones existentes (paso 7). Los pasos siguientes solo son necesarios si se quiere comprobar el emparejamiento por adelantado o aplicar una versión de forma controlada.
2.  Simular la migración y comprobar el emparejamiento:

    ```bash
    php local/educaaragon/cli/migrate_edition_versions.php --course=<shortname> --dry-run --verbose
    ```

3.  Ejecutar la migración real:

    ```bash
    php local/educaaragon/cli/migrate_edition_versions.php --course=<shortname> --verbose
    ```

4.  Opcionalmente, aplicar una versión migrada a los módulos del curso:

    ```bash
    php local/educaaragon/cli/migrate_edition_versions.php --course=<shortname> --apply-version=v1_2025-2026
    ```

**Opciones del script:**

| Opción | Descripción |
|---|---|
| `--course=SHORTNAME` | Filtra por curso (puede repetirse). Sin ella, procesa todos los cursos. |
| `--center=CODIGO` | Filtra por centro completo: migra todas las carpetas de `editions/` cuyo primer token del nombre corto coincida con el código (p. ej. `--center=50020125` migra `50020125-IFC303-16805`, `50020125-IFC303-16809`, …). Combinable con `--course`. |
| `--apply-version=NOMBRE` | Aplica esa versión a los módulos tras migrar. Por defecto **no se aplica ninguna versión**: no hace falta, porque los recursos recién creados ya contienen el contenido `original`. |
| `--include-original` | También copia la carpeta `original` antigua (por defecto se omite, ya que el procesado la regenera). |
| `--dry-run` | Muestra qué haría sin aplicar cambios. |
| `--verbose` | Muestra el detalle de cada recurso. |

**Ejemplo de migración de un centro completo:**

```bash
# Simular primero y comprobar el emparejamiento:
php local/educaaragon/cli/migrate_edition_versions.php --center=50020125 --dry-run --verbose

# Ejecutar de verdad y aplicar la versión migrada:
php local/educaaragon/cli/migrate_edition_versions.php --center=50020125 --apply-version=v1_2025-2026
```

**Documento de log:**

Cada ejecución del script genera automáticamente un documento de log en `<raíz_repo>/editions/_logs/` con todos los cambios realizados (emparejamientos de ids, versiones copiadas, omitidas o aplicadas, errores y cursos no encontrados, cada línea con su fecha y hora) y un **resumen final** con los totales de la ejecución. El nombre del fichero sigue el patrón `migracion_<centro|todos>_<fecha>[_dryrun].log`, por ejemplo `migracion_50020125_20260918_103000.log`.

**Puntos importantes:**

*   El emparejamiento entre carpetas antiguas (ids de la instalación anterior) y los recursos nuevos es **posicional**: se ordenan numéricamente ambas listas y se emparejan por posición. Compruébelo siempre con `--dry-run --verbose` antes de migrar.
*   Las versiones migradas se **copian** (no se mueven) a la carpeta del id nuevo, por lo que empiezan a aparecer automáticamente en el panel de versiones del recurso. No es necesario aplicarlas: el recurso recién creado ya muestra a los alumnos el contenido `original`. El paso 4 (`--apply-version`) solo se usa si se quiere que los alumnos vean directamente una versión migrada.
*   Las carpetas antiguas (ids de la instalación anterior) quedan intactas tras migrar: pueden conservarse como copia de seguridad o eliminarse manualmente del repositorio cuando se haya verificado que las versiones migradas funcionan correctamente.
*   La migración **no genera registros** en la tabla de auditoría `local_educa_edited`: las versiones se ven en el panel de edición, pero no constan en el registro de ediciones.

**Otros scripts CLI relacionados:**

*   `cli/restore_versions.php`: reaplica masivamente la versión `original` de `editions/` a los módulos del curso. Útil para revertir recursos a su estado inicial. Soporta `--course`, `--category`, `--dry-run` y `--verbose`.
*   `cli/reprocess_course.php`: deja un curso como "no procesado" para que la tarea lo regenere. Borra los recursos editables/imprimibles del curso, sus registros en las tablas del plugin y **toda** la carpeta `editions/<shortname>`, incluidas las carpetas de ids antiguos. **Haga una copia de seguridad de `editions/<shortname>` antes de usarlo.**

    ```bash
    php local/educaaragon/cli/reprocess_course.php --shortname=<shortname>
    ```

> **Cuidado con el botón "Reprocesar" de la web:** en el panel de cursos procesados (`processedcourses.php`) hay una acción "reprocesar" que llama a `reprocessing_external::reprocessing_course()`. Esta acción **elimina toda la carpeta `editions/<shortname>` del curso**, incluidas las versiones editadas y las carpetas de ids antiguos, y deja el curso como no procesado. Haga una copia de seguridad de `editions/<shortname>` antes de usarla. Para un reprocesado no destructivo use el lanzador manual (ver *Ejecución manual de tareas*).


Desinstalación
==============

Cuando se desinstale el plugin, **serán eliminadas todas las tablas de base de datos que se crearon durante su instalación, así cómo todo el contenido que exista dentro de la carpeta del repositorio “editions”.**

No se podrá recuperar ninguno de los datos eliminados.
