# Form Element renderer extensions

Plugins can add native Form Elements by pairing a projectable PHP CP UI Component with a Vue renderer under the same stable Form Element Type.

Types must be lowercase namespaced identifiers, such as `color-tools:color-map`. The `craft` namespace is reserved. Treat a published type as a public API: do not rename it within a compatible release. The type is independent of the PHP class name, PHP component-registry name, custom-element tag, and Vue renderer name.

## PHP registration

A plugin Form Element implementation must extend `ViewComponent` and implement `ProjectableFormElement`. Input-shaped controls can extend `ScalarInput`, which provides the component rendering and projection mechanics while leaving the public type and browser primitive explicit:

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

Craft validates the complete batch before registering any class. Non-projectable classes fail during registration. Registering the same owner and class again is harmless. Another class or plugin cannot claim the type, and collision errors identify both claimants. During projection, Craft also verifies that the exact registered class emitted its declared type. Craft derives the plugin handle, display name, and Composer package from the plugin registration; component configuration cannot set or override that ownership.

The registered input can be used like a core input. A `Field` owns its label, instructions, width, read-only state, visibility, and the single projectable input child:

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

Container components extend `FormContainer`, declare `formElementType()`, implement an HTML `tagName()`, and receive ordered projectable descendants through `children()`. Their container metadata is derived from the component contract. The generic Vue renderer traverses those children and passes their rendered output to the registered container renderer's default slot.

To migrate an existing plugin Form Element, replace its `FormElement` or `InputElement` base with an appropriate projectable CP component base, rename `type()` to `formElementType()`, move transport properties from `props()` to `formElementProps()`, add the component's honest HTML rendering path, and compose field presentation with `Field`. Continue using the same stable type and renderer key.

Applications can register ownerless projectable components through the `FormElementTypes` singleton. Ownerless elements must always have an available renderer because Missing Component UI is limited to plugin-owned types.

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

The renderer receives the exported `FormElementRendererProps<TConfig, TValue>` contract: its typed `config`, trusted `attributes`, and an optional binding containing the local Input Name, current value, and effective read-only state. It emits `update:value` to update host-owned state.

```vue
<script setup lang="ts">
  import type {FormElementRendererProps} from '@craftcms/ui'

  type Config = {colors: string[]}

  defineProps<FormElementRendererProps<Config, string>>()
  defineEmits<{ 'update:value': [value: string] }>()
</script>
```

Renderers do not receive the complete definition, Binding Scope, validation collection, routes, submission, or persistence workflow. Plugins deliver PHP and JavaScript together and declare compatible Craft versions through Composer. Optional contract additions may ship in minor Craft releases; required properties or semantic changes require a major release.

If a plugin-owned renderer is unavailable, Craft shows the type and derived plugin ownership. Missing core or application renderers throw. Exceptions from registered renderers produce a separate failed-renderer diagnostic.

## Legacy Settings Islands

The Yii 2 adapter can wrap conventional legacy settings HTML in its internal `yii2-adapter:legacy-settings` Form Element. This compatibility element is not a native plugin authoring API or a general raw-HTML fallback. Plugins should implement a native Form Definition instead.

The island mounts captured head assets, fragment HTML, and body assets in that order, then initializes legacy UI elements. Its live light DOM is serialized before replacement and form submission, and an unchanged keyed island keeps its actual DOM across a complete definition refresh.

This behavior is best-effort. It does not provide native dirty tracking, reset behavior, reactive visibility, field-level errors, file-input serialization, unknown shadow-DOM control serialization, reliable teardown of arbitrary plugin listeners or global side effects, safe shared-asset ownership across islands, new diagnostics for silent asset failures, or full-page lifecycle equivalence. A plugin that depends on those behaviors must migrate to a native Form Definition.

The compatibility element’s lifetime and removal follow the Yii 2 adapter package’s compatibility policy, independently of Craft CMS major releases.
