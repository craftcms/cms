# Color Fields

Color fields give you a hexadecimal color input with a preview of the current color, and on browsers that support `<input type="color">`, clicking on the preview will open the browser’s color picker.

## Templating

Calling a Color field in your templates will return a <api:craft\fields\data\ColorData> object, or `null` if no color was selected.

```twig
{% if entry.linkColor %}
    <style type="text/css">
        .content a {
            color: {{ entry.linkColor.getHex() }};
        }
    </style>
{% endif %}
```
