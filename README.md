# fp-distancia-plugin-edicion-materiales

- [fp-distancia-plugin-edicion-materiales](#fp-distancia-plugin-edicion-materiales)
  - [Plugin de Moodle para la edición de materiales del ministerio](#plugin-de-moodle-para-la-edición-de-materiales-del-ministerio)
- [Repositorio](#repositorio)
    - [Contenido del repositorio](#contenido-del-repositorio)
  - [Configuración](#configuración)
    - [Tarea Programada](#tarea-programada)
    - [Ejecución manual de la tarea](#ejecución-manual-de-la-tarea)
- [Desinstalación](#desinstalación)

## Plugin de Moodle para la edición de materiales del ministerio

El plugin se instala como cualquier otro plugin Local, añadiendo los archivos dentro de la carpeta /local/educaaragon/ y pasando por la administración.

Durante la instalación, el plugin creará en la base de datos las tablas, servicios, eventos, tarea programada y capacidades que necesita para funcionar.

Repositorio
===========

Para que el plugin funcione, es necesario que se cree un repositorio dentro de Moodle del tipo “Sistema de archivos”, con cualquier nombre que permita identificarlo posteriormente.

Los pasos a seguir son los siguientes:

*   Crear una carpeta en **“moodledata/repository”** con el nombre del repositorio. Dentro de ella deben existir las carpetas `materiales` (contendrá los recursos en formato HTML proporcionados por el cliente) y `editions` (la creará y gestionará el plugin para las versiones editadas).
    
*   Dentro de la administración de Moodle, ir a **Administración del sitio→ Extensiones → Repositorios → Gestionar Repositorios → Sistema de archivos**, debe estar marcado como “Activado y visible”
    
*   Pinchando en **“configuración” del Sistema de archivos**, podremos **crear una nueva instancia de repositorio**, donde podremos **asociar la carpeta raíz que hemos creado en “moodledata/repository”**
    
*   La configuración deberá quedar así (con el nombre que deseemos, y con la carpeta correspondiente seleccionada)

### Contenido del repositorio

Dentro del repositorio que acabamos de crear, los contenidos se organizan en dos carpetas principales:

### Carpeta `materiales/` (contenido fuente)

Aquí se colocan los contenidos originales que utilizará la tarea de transformación para dar de alta los recursos editables. Deberán seguir los siguientes requisitos:

*   Dentro de `materiales/` deberá existir **una carpeta nombrada con el nombre corto (`shortname`) del curso** al que corresponda.
    
*   Dentro de la carpeta del curso, debe existir **una carpeta por cada recurso que se vaya a generar**, recomendable que esté nombrada con `01`, `02`, `03`… según el orden de aparición del recurso en el curso, para facilitar la ordenación.
    
*   **Dentro de cada carpeta de un recurso deberán estar todos los ficheros necesarios para que el contenido funcione correctamente, así como un fichero `index.html`** que será el que sirva de disparador del contenido. Si este fichero no existe, el recurso no se generará.

### Carpeta `editions/` (versiones editadas)

Esta carpeta la crea y gestiona el propio plugin. Su estructura es:

```
editions/<shortname_curso>/<resourceid>/
```

Dentro de cada `<resourceid>` se encuentran las versiones del recurso:

*   `original/`: copia del contenido del recurso editable tal como se generó en la primera transformación. No debe editarse ni eliminarse.
*   `v1_2025_2026/`, `v2_.../`, etc.: versiones creadas posteriormente desde el panel de edición.

Si ya existe `editions/<shortname_curso>/`, la tarea de transformación no volverá a crear los recursos desde `materiales/`, sino que reconocerá las versiones ya existentes.
    

## Configuración

Una vez instalado el plugin, para su configuración tendremos hay que ir a **Administración del sitio → Cursos → Educa Aragón → Ajustes generales**

Aquí podremos activar o desactivar el procesamiento de tareas.

Al activarla, se nos mostrarán distintas opciones:

*   **Activar tarea programada para transformar recursos:** activa o desactiva el procesamiento de cursos por la tarea programada (aunque la tarea se ejecute, si esta opción está desmarcada no se procesará ningún curso).
    
*   **Repositorio de contenidos:** selección del repositorio donde están contenidos todos los recursos exportados.
    
*   **Aplicar a todos los cursos:** si se marca esta casilla, todos los cursos de la plataforma serán procesados. Si se desmarca, aparecerá el selector de categorías de curso.
    
*   **Categoría:** el proceso de cursos sólo se harán sobre los cursos que pertenezcan a esta categoría (incluyendo los cursos de las subcategorías)
    

### Tarea Programada

Para configurar la tarea programada del plugin hay que ir a **Administración del sitio → Servidor → Tareas → Tareas Programadas** y en el listado buscar **"Transformar contenidos dinámicos"** (clase `local_educaaragon\task\transform_dynamic_content`)

Desde este panel podrá **configurar la tarea de la misma forma que cualquier otra tarea de moodle, o leer los registros que se han generado durante su ejecución.**

Documentación oficial para configurar tareas programadas: [https://docs.moodle.org/310/en/Scheduled\_tasks](https://docs.moodle.org/310/en/Scheduled_tasks)

Debido a la posible duración de la tarea y a que crea nuevos contenidos en el curso para los estudiantes finales, se recomienda configurar la tarea para que se ejecute una vez al día en horario con poca concurrencia en la plataforma (por defecto, se crea configurada para que pase todos los días a las 3 a.m)

Independientemente del periodo de ejecución que se programe para esta tarea, **se recomienda configurar el cron para que se ejecute cada 30 segundos o cada minuto**, ya que este plugin utiliza eventos del core para realizar ciertos procesos, y sólo se dispararán durante la ejecución del cron.


### Ejecución manual de la tarea

Además de la ejecución programada, la tarea puede lanzarse de forma manual cuando sea necesario. Existen dos métodos:

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


Desinstalación
==============

Cuando se desinstale el plugin, **serán eliminadas todas las tablas de base de datos que se crearon durante su instalación, así cómo todo el contenido que exista dentro de la carpeta del repositorio “editions”.**

No se podrá recuperar ninguno de los datos eliminados.
