# Element select input

`<craft-element-select-input>`, `<craft-entry-select-input>`, and
`<craft-asset-select-input>` progressively enhance the existing light-DOM
element list markup with their corresponding input controllers. Their `settings`
attribute replaces inline JavaScript boots, while `input-class` preserves the
existing plugin subclass escape hatch.

The controllers still use the `Craft` and jQuery page globals for legacy element
indexes, chip actions, rendering, and animation. The custom element is the public
interface for modern callers; the `Craft.*ElementSelectInput` registrations remain
compatibility shims.
