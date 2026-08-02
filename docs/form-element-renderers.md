# Form Definition authoring and renderer extensions

CP UI Components are the sole PHP authoring interface for native Form Definitions. `FormDefinition` projects configured components to immutable `FormElementData`; Vue renderers adapt that transport data to the browser runtime. Plugins extend the same model by pairing a PHP CP UI Component that implements `FormElement` with a Vue renderer under one stable Form Element Type.

Types must be lowercase namespaced identifiers, such as `color-tools:color-map`. The `craft` namespace is reserved. Treat a published type as a public API: do not rename it within a compatible release. The type is independent of the PHP class name, PHP component-registry name, custom-element tag, and Vue renderer name.

## PHP registration

A plugin Form Element implementation must extend `ViewComponent` and implement `FormElement`. Input-shaped controls can extend `ScalarInput`, which provides the component rendering and projection mechanics while leaving the public type and browser primitive explicit:

```php
use CraftCms\Cms\Cp\Components\ScalarInput;
use CraftCms\Cms\Support\Json;
use Override;

class ColorMap extends ScalarInput
{
    /** @var list<string> */
    private array $colors = [];

    public static function formElementType(): string
    {
        return 'color-tools:color-map';
    }

    /** @param list<string> $colors */
    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    #[Override]
    protected function tagName(): string
    {
        return 'color-tools-map';
    }

    #[Override]
    protected function hostAttributes(): array
    {
        return [
            ...parent::hostAttributes(),
            'colors' => Json::encode($this->colors),
        ];
    }

    #[Override]
    protected function formElementProps(): array
    {
        return ['colors' => $this->colors];
    }
}
```

`tagName()` is the component's HTML output, not a renderer lookup convention. The plugin must ship the corresponding `color-tools-map` browser primitive so server-rendered uses remain functional. Do not return raw HTML from projection or rely on a tag name as an automatic Vue fallback.

Register one or more classes from the plugin:

```php
$this->registerFormElementTypes(
    ColorMap::class,
    Gradient::class,
);
```

Craft validates the complete batch before registering any class. Classes that do not implement `FormElement` fail during registration. Registering the same owner and class again is harmless. Another class or plugin cannot claim the type, and collision errors identify both claimants. During projection, Craft also verifies that the exact registered class emitted its declared type. Craft derives the plugin handle, display name, and Composer package from the plugin registration; component configuration cannot set or override that ownership.

The registered input can be used like a core input. A `Field` owns its label, instructions, width, read-only state, visibility, and its single input Form Element child:

```php
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;

FormDefinition::make([
    Field::make()
        ->label('Palette')
        ->input(
            ColorMap::make()
                ->name('palette')
                ->colors(['red', 'blue']),
        ),
]);
```

Container components extend `FormContainer`, declare `formElementType()`, implement an HTML `tagName()`, and receive ordered Form Element descendants through `children()`. Their container metadata is derived from the component contract. The generic Vue renderer traverses those children and passes their rendered output to the registered container renderer's default slot.

The former `CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement`, `InputElement`, and `FormContainer` authoring bases no longer exist. To migrate an existing plugin, replace that base with an appropriate CP component base that implements the `CraftCms\Cms\Cp\FormDefinitions\Contracts\FormElement` contract, rename `type()` to `formElementType()`, move portable transport properties from `props()` to `formElementProps()`, add the component's honest HTML rendering path, and compose field presentation with `Field`. Continue using the same stable type and renderer key.

`FormElementData` is projection output, not a control to configure or subclass. It keeps the serialized graph and generated TypeScript declaration stable while component classes own PHP configuration and HTML rendering.

Applications can register ownerless Form Element components through the `FormElementTypes` singleton. Ownerless elements must always have an available renderer because Missing Component UI is limited to plugin-owned types.

## Vue registration

Register the renderer through the shared control-panel component registry with the exact name `form-element:<type>`. Registrations can be eager or lazy:

```ts
import ColorMap from './components/ColorMap.vue'

Cp.$components.register('form-element:color-tools:color-map', ColorMap)

Cp.$components.register(
  'form-element:color-tools:gradient',
  () => import('./components/Gradient.vue'),
)
```

PHP component or Form Element Type registration never performs this Vue registration. The plugin's control-panel assets must execute one of these explicit registrations. Registering only the PHP component, its template-facing name, or its custom-element tag leaves the Vue renderer unavailable.

The renderer is an ordinary Vue component. Craft passes the Form Element's type-specific props as flattened Vue props and its resolved HTML attributes as ordinary attributes. A value-bound renderer also receives the host-owned `modelValue`, and every renderer receives the effective `readonly` state. It emits `update:modelValue` to update host-owned state.

```vue
<script setup lang="ts">
  defineProps<{
    colors?: string[]
    modelValue?: string
    readonly?: boolean
  }>()

  defineEmits<{ 'update:modelValue': [value: string] }>()
</script>
```

Final `name`, `id`, required state, accessibility state, and other resolved HTML attributes arrive through the ordinary Vue attribute channel. A renderer must forward applicable attributes to its actual form control. Craft gives host-owned values and attributes final precedence over type-specific props.

The host remains responsible for Binding Scope resolution, final names and IDs, runtime values, validation errors, effective read-only state, visibility, Field Container presentation, traversal, reconciliation, and renderer diagnostics. Renderers do not receive the complete definition, Binding Scope, validation collection, routes, submission, or persistence workflow. Runtime values remain in host state and do not enter the serialized Form Definition.

Use a generated `@craftcms/ui` Vue wrapper directly when its props and transport value already match the Form Element. Keep a custom renderer only when it performs observable semantic work that an ordinary generated wrapper cannot represent. A custom renderer uses the same `modelValue` contract; there is no alternate adapter registration mode or compatibility API.

Plugins deliver PHP and JavaScript together and declare compatible Craft versions through Composer. Optional contract additions may ship in minor Craft releases; required properties or semantic changes require a major release.

If a plugin-owned renderer is unavailable, Craft shows the type and derived plugin ownership. Missing core or application renderers throw. Exceptions from registered renderers produce a separate failed-renderer diagnostic.

## Retained core semantic adapters

Core registers generated wrappers directly unless one of these Form Element Renderers owns additional semantics:

| Form Element Type | Retained responsibility |
| --- | --- |
| `craft:checkbox-select-input` | Restores typed option values, authored and sortable selection order, per-option disabled state, and the special “all” selection. |
| `craft:combobox-input` | Preserves string editing and adds the alias-specific explanatory callout. |
| `craft:date-input` | Truncates date-time input to `YYYY-MM-DD` and converts an empty value to `null`. |
| `craft:editable-table-input` | Connects the explicit source name and column coordination scope while preserving keyed or unkeyed row values. |
| `craft:element-condition-input` | Requests and initializes server-rendered condition UI, applies returned assets, and synchronizes later DOM changes to the host value. |
| `craft:money-input` | Converts between displayed major units and transport minor units at the configured precision without losing large integer values. |
| `craft:number-input` | Converts the browser string value to a numeric or `null` transport value. |
| `craft:select-input` | Renders authored options and restores their original string, number, boolean, or `null` value after DOM selection. |
| `craft:time-input` | Truncates time input to `HH:mm` and converts an empty value to `null`. |

An adapter without a responsibility in this inventory should be replaced by its generated wrapper.

## Legacy Settings Islands

The Yii 2 adapter wraps conventional legacy settings HTML with its `CraftCms\Yii2Adapter\Cp\Components\LegacySettings` CP UI Component, which owns the internal `yii2-adapter:legacy-settings` Form Element Type. This compatibility component is not a native plugin authoring API or a general raw-HTML fallback. Plugins should implement a native Form Definition instead.

The island mounts captured head assets, fragment HTML, and body assets in that order, then initializes legacy UI elements. Its live light DOM is serialized before replacement and form submission, and an unchanged keyed island keeps its actual DOM across a complete definition refresh.

This behavior is best-effort. It does not provide native dirty tracking, reset behavior, reactive visibility, field-level errors, file-input serialization, unknown shadow-DOM control serialization, reliable teardown of arbitrary plugin listeners or global side effects, safe shared-asset ownership across islands, new diagnostics for silent asset failures, or full-page lifecycle equivalence. A plugin that depends on those behaviors must migrate to a native Form Definition.

The compatibility element’s lifetime and removal follow the Yii 2 adapter package’s compatibility policy, independently of Craft CMS major releases.
