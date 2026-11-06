# Element select input

`<craft-element-select-input>`, `<craft-entry-select-input>`, and
`<craft-asset-select-input>` progressively enhance the existing light-DOM
element list markup with their corresponding input controllers. Their `settings`
attribute replaces inline JavaScript boots, while `input-class` preserves the
existing plugin subclass escape hatch.

`_includes/forms/elementSelect.twig` always boots through these elements. A
core `jsClass` (`Craft.EntrySelectInput`, `Craft.AssetSelectInput`) picks the
matching element; any other class name becomes `input-class`, which the Yii2
adapter resolves to the plugin's class. The `Craft.*SelectInput` globals are
the adapter's own jQuery-based copies
(`yii2-adapter/resources/js/element-select-input`), kept for plugins that
subclass them; core's classes aren't on `Craft`.
