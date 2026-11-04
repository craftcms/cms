# Element select input

`<craft-element-select-input>`, `<craft-entry-select-input>`, and
`<craft-asset-select-input>` progressively enhance the existing light-DOM
element list markup with their corresponding input controllers. Their `settings`
attribute replaces inline JavaScript boots, while `input-class` preserves the
existing plugin subclass escape hatch.

The `ElementSelect` component (`FormFields::elementSelectHtml()`) always boots
through these elements, picking one from the element type. Any non-core input
class becomes `input-class`, which the Yii2 adapter resolves to the plugin's
class. The adapter's legacy `_includes/forms/elementSelect.twig` still boots its
`jsClass` inline for plugins that extend it. The `Craft.*SelectInput` globals are
the adapter's own jQuery-based copies
(`yii2-adapter/resources/js/element-select-input`), kept for plugins that
subclass them; core's classes aren't on `Craft`.
