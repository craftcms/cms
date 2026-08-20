# Control Panel Forms

Craft's Control Panel Form system describes a form once in PHP and renders the resolved payload through either the PHP
renderer or the Inertia/Vue renderer. It does not own validation, authorization, persistence, routes, requests, CSRF, or
save actions. Those remain the host controller's responsibility.

Use this system for Control Panel settings and element-editing interfaces. It is not a site-form API and it does not
render an HTML `<form>` element.

## Form boundaries

A `Form` contains an ordered tree of `Node` objects. A `Field` Node contains one `Control`. Structural Nodes contain
other Nodes, and a Control may own nested Forms for editors such as Matrix and Content Block.

Resolve a Form with an explicit `FormContext`:

```php
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;

$payload = app(FormResolver::class)->resolve(
    $component->settingsForm($context),
    new FormContext(
        namespace: 'settings',
        values: $submittedValues,
        errors: $errors,
    ),
);
```

The payload contains JSON-safe definitions, canonical values, path-addressed errors, and global errors. It contains no
callbacks, raw HTML, JavaScript, asset metadata, authorization policy, or persistence behavior.

`FormContext` accepts namespaces and Control paths as segment lists or dot strings. Resolution normalizes them to
absolute segment paths. Pathless Nodes need an explicit stable UID; do not derive it from array position or generate it
while rendering.

## Replacement interfaces

### Plugin settings

Plugins override `Plugin::settingsForm()` for their standard settings page:

```php
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;

public function settingsForm(FormContext $context = new FormContext): ?Form
{
    return Form::make([
        Field::make(
            t('API key', category: 'my-plugin'),
            Text::make('apiKey'),
        ),
    ]);
}
```

Control paths are relative to the settings model. Craft supplies its current values and errors under the `settings`
namespace and renders editable or read-only mode as required. The standard editable page requires a settings model and
a Form. Plugins may still override `getSettingsResponse()` or `getReadOnlySettingsResponse()` to own the complete
response and bypass the standard Form page.

### Component settings

Implement `ConfigurableComponentInterface::settingsForm()` instead of `getSettingsHtml()`:

```php
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;

public function settingsForm(FormContext $context = new FormContext): ?Form
{
    return Form::make([
        Field::make(
            t('API key', category: 'my-plugin'),
            Text::make('apiKey')->value($this->apiKey),
        ),
    ]);
}
```

Return `null` when the component has no settings. Translate server-authored labels, instructions, options, and errors
before resolution.

### Field inputs

Implement `FieldInterface::formControl()` instead of `getInputHtml()`:

```php
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Controls\Text;

public function formControl(FieldContext $context): Control
{
    return Text::make($context->path)
        ->value($context->value)
        ->mode($context->mode);
}
```

The FieldLayout compiler owns the surrounding `Field` Node, its label, instructions, required state, width, conditions,
and layout identity. A field type owns only its editing Control and canonical value shape.

### FieldLayout elements

Implement `FieldLayoutElement::formNode()` instead of `formHtml()`:

```php
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Nodes\MarkdownContent;

public function formNode(FieldLayoutElementContext $context): ?Node
{
    return MarkdownContent::make($this->uid, $this->content);
}
```

Return zero or one root Node. It may contain children or a composite Control. The compiler applies the Form context and
mode. Listen for `FieldLayoutFormResolving` to add, remove, or reorder typed Nodes after compilation; do not mutate
rendered HTML or persisted layout data.

### FieldLayout component settings

Field layout components — tabs and layout elements — describe the form shown in the designer's settings slideout by
implementing `settingsNodes()` instead of `settingsHtml()`:

```php
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;

protected function settingsNodes(FormContext $context): array
{
    return [
        Field::make(t('Heading'), Text::make('heading')->value($this->heading)),
    ];
}
```

Return a list of Nodes with paths relative to the component's config, so a Control at `heading` posts back as the
component's `heading` setting. `FieldLayoutComponent::settingsForm()` is `final`: it composes `settingsNodes()` and
`conditionalSettingsNodes()`, separating them with a `Separator` Node when both are present, and returns `null` when
neither produces a Node.

`conditionalSettingsNodes()` supplies the visibility condition builders. Override it to append further condition
groups — `CustomField` adds its editability conditions this way — and use `conditionGroupNode()` to build a group
with the standard user/element condition pair.

The settings scope is `settings`, and the form is refreshable: a `discrete` change posts back to
`fields/refresh-layout-component-settings`, which rebuilds the component from the posted values and re-resolves the
form. Use that instead of client-side scripting when one setting should change another's state — hiding a field's
label, for instance, disables its label Control on the next refresh.

## Custom Nodes and Controls

Use a core Node or Control when one already has the required value shape and behavior. A plugin-specific type is needed
only when both renderers require new semantics.

A custom Node implements `CraftCms\Cms\Form\Contracts\Node`. A custom Control can extend
`CraftCms\Cms\Form\Controls\Control`. Each type must provide:

- a stable PHP class identity;
- a unique Vue component registry key;
- JSON-safe type-specific properties;
- a PHP `renderHtml()` implementation; and
- a Vue component with equivalent values, modes, errors, submission, and accessibility behavior.

Container Nodes can extend `CraftCms\Cms\Form\Nodes\Container`, which provides stable UID storage, ordered children,
fluent and conditional child addition, and the standard no-Control behavior.

### Field actions

A `Field` Node can carry action Nodes in its heading, rendered into `<craft-field>`'s `actions` slot — a hide-label
toggle, a copy-value button, a settings menu:

```php
use CraftCms\Cms\Form\Controls\Checkbox;
use CraftCms\Cms\Form\Nodes\Action;

Field::make(t('Label'), Text::make('label'))
    ->actions(Action::make(
        Checkbox::make('labelHidden')->label(t('Hide')),
    ));
```

Actions are resolved as ordinary child Nodes, so each one's Control gets its own path, value binding, mode, and error
binding — an action is a real posting Control, not decoration. `Action` renders its Control without the surrounding
field chrome.

Register the PHP types during plugin boot:

```php
use CraftCms\Cms\Form\FormControlTypes;
use CraftCms\Cms\Form\FormNodeTypes;

public function boot(
    FormNodeTypes $nodeTypes,
    FormControlTypes $controlTypes,
): void {
    $nodeTypes->register(Notice::class);
    $controlTypes->register(Slug::class);
}
```

Register the Vue components from the plugin's existing Control Panel asset bundle. Do not introduce a Form-specific
asset loader:

```ts
Cp.booting((cp) => {
  cp.$components.register('my-plugin:notice', NoticeNode)
  cp.$components.register('my-plugin:slug', SlugControl)
})
```

The registry also accepts lazy component loaders. Register components before the Inertia application mounts.

A Control component rendered by the core `Field` Node receives `control`, `value`, `label`, `editable`, `invalid`,
`required`, full `values` and `errors`, touched paths, and the containing scope. Emit `update:value` with the canonical
value and optionally `typing` or `discrete` as the change kind. Only editable Controls may submit a `name` or apply
browser-required constraints. Use `inputName(control.path)` for the ordinary nested request shape.

The test plugin contains complete reference implementations:

- `tests/TestClasses/TestPlugin/src/Form/Nodes/Notice.php`
- `tests/TestClasses/TestPlugin/src/Form/Controls/Slug.php`
- `tests/TestClasses/TestPlugin/resources/js/NoticeNode.vue`
- `tests/TestClasses/TestPlugin/resources/js/SlugControl.vue`
- `tests/TestClasses/TestPlugin/resources/js/register-form-components.ts`

Resolution rejects unregistered types and non-JSON-safe properties. Either renderer invalidates the complete Form when a
registered component is missing, its loader fails, or rendering throws. Do not catch these failures and submit a partial
Form.

## Modes, errors, and refresh

Controls resolve to `editable`, `readOnly`, or `disabled` mode. Read-only and disabled Controls display canonical values
but do not mutate or submit them. Controller authorization is mandatory for initial pages, refreshes, and mutations;
hiding or disabling a Control is not authorization.

Errors use absolute Control paths. The resolver assigns descendant errors to the longest matching Control path; errors
without an owner remain global. Renderers provide the shared label, instructions, required, invalid, feedback, and ARIA
relationships. Custom renderers must preserve these semantics and escape plain-text properties. Render Markdown only
through the documented Markdown types.

Refreshable scopes send the complete current scoped value snapshot without persistence. The client owns unsaved values,
dirty and touched state, focus, and selected tabs. Hidden paths retain transient values but are omitted from mutations.
Changed delta groups submit complete canonical group values, including explicit empty values.

Because the client owns them, no payload arriving from the server clears unsaved values — only the host can, by calling
the renderer's `resetValues()`. Reserve it for the case where the user has abandoned their edits outright, such as
discarding a provisional draft; it seeds the Form again from the payload and leaves it untouched and undirty.

## Missing providers

Persisted FieldLayout types supplied by an unavailable plugin resolve to visible missing-provider placeholders. They submit
no value and preserve the original type and opaque configuration so reinstalling or enabling the provider can recover the
layout and content.

This recovery applies only to persisted unavailable types. A live but unregistered Node or Control is a programming
error and invalidates the Form.

## Yii2 adapter behavior

Legacy HTML compatibility belongs exclusively to `craftcms/yii2-adapter`.

Yii-era plugin classes keep their protected `settingsHtml()` hook; the adapter captures it into the plugin's
`settingsForm()`.

Plugins that extend the adapter's Yii-era component, field, or FieldLayout element classes keep their existing
`getSettingsHtml()`, `getInputHtml()`, `getStaticHtml()`, and `formHtml()` overrides. The adapter implements the modern
`settingsForm()`, `formControl()`, and `formNode()` contracts with private Legacy HTML islands. Normal PHP method
overriding still allows a plugin to provide a native modern implementation instead.

For direct implementations, use the relevant adapter contract and trait together:

- `LegacySettingsComponent` with `LegacySettingsForm`;
- `LegacyField` with `LegacyFieldControl` and `LegacySettingsForm`; or
- an adapter `FieldLayoutElement`, which already uses `LegacyFormNode`.

The adapter captures legacy HTML once under the host namespace, including registered head and body assets and inline
initializers. It preserves zero, one, or multiple named input roots and maps editable, read-only, static, and disabled
modes to the established hooks. Both renderers consume the same captured fragment. A nullable settings or FieldLayout
hook omits the Node. Field input hooks must return HTML; `null` produces a compatibility error.

Legacy refresh uses the normal Form scope protocol. A selected file input cannot cross the JSON-safe refresh bridge and
produces a targeted error instead of silently dropping the upload. Capture, parsing, mounting, asset, and initializer
failures invalidate the complete Form. Compatibility is runtime-only: it does not rewrite plugin settings, field types,
layout class names, UIDs, or opaque configuration.

## Conformance checklist

Before shipping a plugin Form, verify both renderers where the host supports them:

- canonical values and ordinary nested submission names;
- editable, read-only, and disabled modes;
- root, descendant, and global validation errors;
- labels, instructions, required and invalid state, groups, and error relationships;
- keyboard operation, logical focus order, visible focus, screen-reader output, contrast, forced colors, and 320-pixel
  reflow;
- translated server-authored and renderer-owned text;
- escaping of labels, instructions, values, errors, and type-specific properties;
- refresh reconciliation, stale-response rejection, failure recovery, hidden-value restoration, and delta groups when
  refresh is enabled;
- missing-provider recovery for persisted extension types; and
- loud failure for missing live registrations or renderer exceptions.

Core's reference coverage lives in `tests/Unit/Form`, `tests/Feature/Form`,
`resources/js/modules/forms/*.test.ts`, and `yii2-adapter/tests-laravel/Legacy/LegacyHtmlFormTest.php`.
