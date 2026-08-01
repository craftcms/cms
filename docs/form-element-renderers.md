# Form Element renderer extensions

Plugins can add native Form Elements by registering a PHP Form Element class and a Vue renderer under the same stable Form Element Type.

Types must be lowercase namespaced identifiers, such as `color-tools:color-map`. The `craft` namespace is reserved. Treat a published type as a public API: do not rename it within a compatible release.

## PHP registration

An input class extends `InputElement`, declares its type independently of its PHP class name, and exposes typed fluent configuration. `InputElement::make()` supplies the local Input Name and projects the required Field Container:

```php
use CraftCms\Cms\Cp\FormDefinitions\Elements\InputElement;

class ColorMap extends InputElement
{
    /** @var list<string> */
    private array $colors = [];

    public static function type(): string
    {
        return 'color-tools:color-map';
    }

    /** @param list<string> $colors */
    public function colors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    protected function props(): array
    {
        return ['colors' => $this->colors];
    }
}
```

Register one or more classes from the plugin:

```php
$this->registerFormElementTypes(
    ColorMap::class,
    Gradient::class,
);
```

Craft validates the complete batch before registering any class. Registering the same class again is harmless. A different class cannot replace an existing type. Craft derives the plugin handle, display name, and Composer package from the plugin registration; Form Element configuration cannot set or override that ownership.

The registered input can be used like a core input. Its label, instructions, width, read-only state, attributes, and visibility use the inherited fluent API:

```php
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;

FormDefinition::make([
    ColorMap::make('palette')
        ->label('Palette')
        ->colors(['red', 'blue']),
]);
```

Container Form Elements extend `FormElement`, override `isContainer()` to return `true`, and return their ordered elements from `children()`. The generic renderer traverses those children and passes their rendered output to the Vue renderer’s default slot.

Applications can register ownerless Form Elements through the `FormElementTypes` singleton. Ownerless elements must always have an available renderer because Missing Component UI is limited to plugin-owned types.

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
