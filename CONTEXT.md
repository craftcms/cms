# Craft CMS

Craft CMS models content and control-panel configuration while supporting extension by plugins.

## Language

**Form**:
A structured, serializable description of a form’s renderable structure and presentation behavior. It does not own values, validation, authorization, routing, submission, or persistence.
_Avoid_: Form schema, blueprint, form layout

**Form Element**:
A renderable item within a Form. Form Elements may contain other Form Elements, but their capabilities and valid configuration are determined by their type.

**Form Element Type**:
A stable, lowercase namespaced public name that defines a Form Element’s capabilities and presentation. It is independent of any PHP class or Vue component name. Core reserves the `craft` namespace; plugins own their chosen namespace and its collision risk.

**Form Element Renderer**:
An ordinary Vue component that receives flattened type-specific props, resolved HTML attributes, host-owned `modelValue`, and effective `readonly`, and emits `update:modelValue`. A custom renderer is retained only when it performs observable semantics that a generated Vue wrapper cannot represent. Generic Form rendering remains responsible for names, values, layout, visibility, errors, accessibility wiring, child traversal, reconciliation, and renderer diagnostics.

**Input Name**:
A Form-local name uniquely identifying the controller-owned value edited by an input Form Element. It excludes any Binding Scope supplied where the Form is rendered.

**Binding Scope**:
The portion of a host form’s values and validation errors within which a Form’s Input Names resolve. It belongs to the rendering context rather than the Form.

**Form Container**:
A Form Element that visually organizes child Form Elements. It does not create a Binding Scope or alter their Input Names.

**Field Container**:
A Form Container that presents a label, instructions, validation feedback, accessibility wiring, and related field-level chrome around one input Form Element. It is a rendering concept, not a persisted Craft content field.

**Visibility Condition**:
A declarative predicate over values within a Binding Scope that determines whether a Form Element is displayed. It does not alter values, validation, submission, or persistence.

**Missing Component**:
A diagnostic placeholder for a configured plugin component whose implementation is unavailable. It preserves the component’s expected identity, configuration, and available plugin ownership metadata without substituting another implementation.

**Legacy Settings Island**:
A compatibility rendering of a configurable component’s legacy settings within a modern settings surface. Its live DOM remains authoritative, and it does not participate in native Form value or reactivity semantics.

**Field Layout**:
A persisted, element-specific arrangement and policy for the fields and presentational elements used to edit an element. When presented as a form, a Field Layout is expressed as a Form rather than acting as a second rendering model.
