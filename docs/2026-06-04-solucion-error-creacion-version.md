**¿Qué pasaba?**

En `createversion_external.php` faltaba la validación del contexto del curso antes de crear la instancia de `manage_editable_resource`. Cuando el repositorio filesystem ejecuta `get_listing()`, internamente puede inicializar el tema y el output de Moodle, y eso requiere que `$PAGE->context` esté definido. `self::validate_context()` es el encargado de hacerlo.

**¿Por qué solo fallaba en entornos no productivos?**

Por el nivel de **debugging**. En desarrollo y pruebas suele estar en `DEBUG_DEVELOPER` o `DEBUG_NORMAL`, lo que hace que Moodle sea mucho más estricto con la inicialización del renderer, el tema y el output. En producción, con debugging mínimo o desactivado (`DEBUG_NONE`), esos checks no son tan agresivos y el error no se manifiesta.

**¿Qué he corregido?**

He añadido la validación de contexto y permisos (que además era un agujero de seguridad) a las funciones externas que la carecían:

- `classes/external/createversion_external.php`
- `classes/external/deleteversion_external.php`
- `classes/external/process_resource_links.php`

Ahora las tres siguen el mismo patrón que ya tenías en `applyversion_external`, `savechanges_external` y `savetocchanges_external`:

```php
$coursecontext = context_course::instance($courseid);
self::validate_context($coursecontext);
require_capability('local/educaaragon:editresources', $coursecontext);
```

Esto establece `$PAGE->context` correctamente y evita el `coding_exception`. Prueba a crear una versión en el entorno de desarrollo y ya debería funcionar.