# Craft CMS

Craft CMS core and its workspace packages share one domain language.

## Control Panel forms

**Form**:
A renderer-neutral definition of an ordered tree of Nodes for a Control Panel form. It is not an HTML `<form>`; nested-editing Controls may own nested Forms.
_Avoid_: Form definition, FormDefinition

**Settings Form**:
A Form returned by a configurable Control Panel component to describe its settings interface. Its host supplies the runtime mode and renders it.
_Avoid_: Settings HTML, settings config

**Node**:
An ordered structural or presentational item in a Form. Fields, groups, and display content are Nodes; Controls are not. Each concrete Node owns its PHP rendering and Vue component hooks. A pathless structural or presentational Node requires an explicit stable `uid`.
_Avoid_: Form item, form element, form component

**Field**:
A Form node that presents one Control with its label, instructions, required state, and other field-level semantics. A FieldLayout compiler owns this wrapper for persisted layout elements.
_Avoid_: Input wrapper

**Field type**:
A Craft extension type that defines content behavior such as normalization, persistence, querying, and its editing Control. Dropdown, Radio Buttons, and Checkboxes remain distinct field types even when they use the shared Choice Control.
_Avoid_: Control, Form Field

**Control**:
The definition of a form interaction. Its resolved mode is `editable`, `readOnly`, or `disabled`, and its effective submission path is its reconciliation identity. Read-only and disabled Controls display their values but do not submit them. Each concrete Control owns its PHP rendering and Vue component hooks. Field types define Controls; they do not define their surrounding FieldLayout Field.
_Avoid_: Input, component

**FormPayload**:
Craft's documented, JSON-safe resolution of a Form, consumed by both PHP and Vue renderers. Craft's release compatibility governs the contract; the payload has no runtime schema version.
_Avoid_: Form config, component config

**FormContext**:
The explicit runtime facts Craft needs to resolve a Form, such as its namespace, errors, and editing mode.
_Avoid_: Request globals, ambient form state

**Form scope**:
An absolute path and Node subtree that refreshes and reconciles as a unit within a FormPayload. The root Form uses the empty scope.
_Avoid_: Form fragment, refresh region

**FormResolver**:
The Craft-owned module that resolves a Form and FormContext into a FormPayload.
_Avoid_: Form compiler

**Delta group**:
The canonical value subtree treated atomically when constructing a changed-only mutation. A value-bearing Control defaults to its own effective path.
_Avoid_: Delta name, modified path

**FieldLayout**:
The persisted arrangement and instance-specific configuration of element fields and display content. It is resolved with an edited element and FormContext into FormPayload; the intermediate Form is not part of its caller-facing contract.
_Avoid_: Form layout

**FieldLayoutElement**:
A persisted item within a FieldLayout. For an editing context, it contributes zero or one root Node, which may contain child Nodes or a composite Control. It retains layout identity and configuration but is not itself a Node.
_Avoid_: Form element, Node

**Legacy HTML island**:
A compatibility region whose HTML, submitted inputs, and client behavior remain owned by a legacy plugin rather than semantic Form Nodes and Controls.
_Avoid_: Legacy Form, HTML Control
