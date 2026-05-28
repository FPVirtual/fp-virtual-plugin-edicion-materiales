# Migración a PHP 8.2 / 8.3 — local_educaaragon

## Resumen

Este documento recoge los cambios realizados en el plugin `local_educaaragon` para garantizar la compatibilidad con **PHP 8.2** y **PHP 8.3**, eliminando APIs y funciones que han sido deprecadas o eliminadas en estas versiones.

## Funciones afectadas

### 1. `utf8_encode()` / `utf8_decode()` — Eliminadas en PHP 8.3

- **Referencia PHP:** [PHP Manual — utf8_encode](https://www.php.net/manual/en/function.utf8-encode.php)
- **Estado:** Deprecadas en PHP 8.2, eliminadas en PHP 8.3.
- **Reemplazo:** `mb_convert_encoding($str, $to_encoding, $from_encoding)`

### 2. `mb_convert_encoding(..., 'HTML-ENTITIES', ...)` — Eliminado en PHP 8.3

- **Referencia PHP:** [PHP Manual — mb_convert_encoding](https://www.php.net/manual/en/function.mb-convert-encoding.php)
- **Estado:** El target `'HTML-ENTITIES'` fue deprecado en PHP 8.2 y eliminado en PHP 8.3.
- **Reemplazo:** `mb_encode_numericentity($text, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8')`, encapsulado en la función helper `encode_html_entities()`.

### 3. `FILTER_SANITIZE_URL` — Deprecado en PHP 8.1

- **Referencia PHP:** [PHP Manual — filter_var](https://www.php.net/manual/en/function.filter-var.php)
- **Estado:** Constante deprecada en PHP 8.1.
- **Reemplazo:** `clean_param($value, PARAM_URL)` de la API nativa de Moodle.

---

## Archivos modificados

### `lib.php`

- **Línea 13:** Reemplazado `utf8_encode($str)` por `mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1')`.
- **Nueva función:** `encode_html_entities(string $text): string` — Helper para convertir UTF-8 a entidades HTML numéricas compatibles con `DOMDocument`.

### `classes/manage_editable_resource.php`

- **Línea 262:** Reemplazado `utf8_decode($value)` por `mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8')`.
- **Línea 372:** Reemplazado `utf8_decode($newHtml)` por `mb_convert_encoding($newHtml, 'ISO-8859-1', 'UTF-8')`.
- **Línea 359:** Eliminado `mb_convert_encoding($filepath, 'HTML-ENTITIES', 'UTF-8')` en `loadHTMLFile()` (innecesario para rutas de archivo).
- **Línea 365:** Reemplazado `mb_convert_encoding(..., 'HTML-ENTITIES', ...)` por `encode_html_entities(...)`.
- **Línea 405:** Reemplazado `mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8')` por `encode_html_entities($html)`.
- **Línea 468:** Reemplazado `mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8')` por `encode_html_entities($html)`.
- **Línea 470:** Reemplazado `mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8')` por `encode_html_entities($html)`.
- **Línea 479:** Eliminado `mb_convert_encoding($filepath, 'HTML-ENTITIES', 'UTF-8')` en `loadHTMLFile()` (innecesario para rutas de archivo).

### `classes/registereditions_table.php`

- **Línea 116:** Reemplazado `utf8_encode(base64_decode($data))` por `mb_convert_encoding(base64_decode($data), 'UTF-8', 'ISO-8859-1')`.

### `classes/manage_logs.php`

- **Línea 315:** Reemplazado `filter_var($link, FILTER_SANITIZE_URL)` por `clean_param($link, PARAM_URL)`.

---

## Notas técnicas

- **`mb_encode_numericentity`**: Convierte caracteres multibyte a entidades numéricas HTML (ej: `á` → `&#225;`). Es la alternativa recomendada por la comunidad PHP tras la eliminación del target `HTML-ENTITIES`.
- **`clean_param($link, PARAM_URL)`**: API nativa de Moodle que valida y limpia URLs de forma más robusta que `filter_var()`, ya que respeta la arquitectura de filtros del core y está mantenida por el proyecto Moodle.
- **Rutas de archivo (`$filepath`)**: El uso previo de `mb_convert_encoding($filepath, 'HTML-ENTITIES', 'UTF-8')` era conceptualmente incorrecto. Una ruta del sistema de archivos no debe convertirse a entidades HTML; se pasa directamente a `loadHTMLFile()`.

---

## Rama de implementación

`feature/php82-compatibility`

## Verificación post-implementación

```bash
# Verificar que no quedan funciones obsoletas
grep -rn "utf8_encode\|utf8_decode" --include="*.php" .
grep -rn "HTML-ENTITIES" --include="*.php" .
grep -rn "FILTER_SANITIZE_URL" --include="*.php" .

# Verificar sintaxis PHP
php -l lib.php
php -l classes/manage_editable_resource.php
php -l classes/registereditions_table.php
php -l classes/manage_logs.php
```
