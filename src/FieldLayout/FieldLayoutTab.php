<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use Closure;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Heading;
use CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule;
use CraftCms\Cms\FieldLayout\LayoutElements\LineBreak;
use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;
use CraftCms\Cms\FieldLayout\LayoutElements\Missing;
use CraftCms\Cms\FieldLayout\LayoutElements\Template;
use CraftCms\Cms\FieldLayout\LayoutElements\Tip;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Override;
use RuntimeException;

use function CraftCms\Cms\t;

/**
 * @property FieldLayoutElement[]|null $elements The tab’s layout elements
 * @property FieldLayout|null $layout The tab’s layout
 *
 * @phpstan-type TabConfig array{id?: int, layoutId?: int, name?: string|null, sortOrder?: int, uid?: string, userCondition?: array<string, mixed>|null, elementCondition?: array<string, mixed>|null, fields?: array<string, array{sortOrder: int, required: bool}>, elements?: list<array<string, mixed>>}
 */
class FieldLayoutTab extends FieldLayoutComponent
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null Layout ID
     */
    public ?int $layoutId = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var int|null Sort order
     */
    public ?int $sortOrder = null;

    /**
     * @see getLayout()
     * @see setLayout()
     */
    private FieldLayout $_layout;

    /**
     * @var FieldLayoutElement[] The tab’s layout elements
     *
     * @see getElements()
     * @see setElements()
     */
    private array $_elements = [];

    /** @param array<string, mixed> $config */
    public function __construct(array $config = [])
    {
        // Config normalization
        if (! array_key_exists('elements', $config)) {
            parent::__construct($config);

            return;
        }

        if (is_string($config['elements'])) {
            $config['elements'] = Json::decode($config['elements']);
        }

        if (! is_array($config['elements'])) {
            unset($config['elements']);
        }

        parent::__construct($config);
    }

    /**
     * Creates a new field layout tab from the given config.
     *
     * @param  TabConfig  $config
     */
    public static function createFromConfig(array $config): self
    {
        static::updateConfig($config);

        return new self($config);
    }

    /**
     * Returns the label HTML that should be displayed within field layout designers.
     */
    public function labelHtml(): string
    {
        return
            Html::tag('h3', Html::encode($this->name), [
                'class' => 'fld-tab__name',
            ]).
            ($this->hasConditions() ? Html::tag('div', Icons::svg('diamond'), [
                'class' => array_filter(array_merge(['cp-icon', 'puny', 'orange'])),
                'title' => t('This tab is conditional'),
                'aria' => ['label' => t('This tab is conditional')],
            ]) : '');
    }

    /**
     * Updates a field layout tab’s config to the new format.
     *
     * @param  TabConfig  $config
     */
    public static function updateConfig(array &$config): void
    {
        if (! array_key_exists('fields', $config)) {
            return;
        }

        $fields = $config['fields'];
        uasort($fields, fn (array $a, array $b) => $a['sortOrder'] <=> $b['sortOrder']);
        $config['elements'] = [];

        foreach ($fields as $fieldUid => $fieldConfig) {
            $config['elements'][] = [
                'type' => CustomField::class,
                'fieldUid' => $fieldUid,
                'required' => (bool) $fieldConfig['required'],
            ];
        }

        unset($config['fields']);
    }

    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), ['sortOrder']);
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'id' => ['nullable', 'integer'],
            'layoutId' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'sortOrder' => ['nullable', 'string', 'max:4'],
        ]);
    }

    #[Override]
    public function hasSettings(): bool
    {
        return true;
    }

    #[Override]
    protected function settingsNodes(FormContext $context): array
    {
        return [
            Field::make(t('Name'), Text::make('name')->value($this->name))->required(),
        ];
    }

    /**
     * Returns the field layout tab’s config.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $this->uid ??= Str::uuid()->toString();

        $config = $this->toArray(['name', 'uid', 'userCondition', 'elementCondition']);
        $config['elements'] = $this->getElementConfigs();

        return $config;
    }

    /**
     * Returns the tab’s elements’ configs.
     *
     * @return list<array<string, mixed>>
     */
    public function getElementConfigs(): array
    {
        $elementConfigs = [];

        foreach ($this->getElements() as $layoutElement) {
            $layoutElement->uid ??= Str::uuid()->toString();

            $elementConfigs[] = $layoutElement instanceof Missing
                ? ['type' => $layoutElement->expectedType] + ($layoutElement->settings ?? [])
                : ['type' => $layoutElement::class] + $layoutElement->toArray();
        }

        return $elementConfigs;
    }

    /**
     * Returns the tab’s layout.
     *
     * @return FieldLayout The tab’s layout.
     *
     * @throws RuntimeException if [[layoutId]] is set but invalid
     */
    #[Override]
    public function getLayout(): FieldLayout
    {
        if (isset($this->_layout)) {
            return $this->_layout;
        }

        if (! $this->layoutId) {
            throw new RuntimeException('Field layout tab is missing its field layout.');
        }

        if (($this->_layout = app(Fields::class)->getLayoutById($this->layoutId)) === null) {
            throw new RuntimeException('Invalid layout ID: '.$this->layoutId);
        }

        return $this->_layout;
    }

    /**
     * Sets the tab’s layout.
     *
     * @param  FieldLayout  $layout  The tab’s layout.
     */
    #[Override]
    public function setLayout(FieldLayout $layout): void
    {
        $this->_layout = $layout;
    }

    /**
     * Returns the tab’s layout elements.
     *
     * @return FieldLayoutElement[]
     */
    public function getElements(): array
    {
        return $this->_elements;
    }

    /**
     * Sets the tab’s layout elements.
     *
     * @phpstan-param array<FieldLayoutElement|array{type:class-string<FieldLayoutElement>}> $elements
     */
    public function setElements(array $elements): void
    {
        $fieldsService = app(Fields::class);
        $pluginsService = app(Plugins::class);
        $this->_elements = [];

        foreach ($elements as $layoutElement) {
            if (is_array($layoutElement)) {
                try {
                    $layoutElement = $fieldsService->createLayoutElement($layoutElement);
                    /** @phpstan-ignore catch.neverThrown */
                } catch (FieldNotFoundException) {
                    // Skip quietly
                    continue;
                } catch (InvalidArgumentException $e) {
                    Log::warning('Invalid field layout element config: '.$e->getMessage(), [__METHOD__]);
                    report($e);

                    continue;
                }
            }

            // if layout element belongs to a plugin, ensure the plugin is installed
            $pluginHandle = $pluginsService->getPluginHandleByClass($layoutElement::class);
            if ($pluginHandle === null || $pluginsService->isPluginEnabled($pluginHandle)) {
                $layoutElement->setLayout($this->getLayout());
                $this->_elements[] = $layoutElement;
            }
        }

        // Clear caches
        if (isset($this->_layout)) {
            $this->_layout->reset();
        }
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Adds elements to the tab.
     *
     * Multi-instance custom fields need a unique handle override when included more than once.
     */
    public function add(FieldLayoutElement ...$elements): static
    {
        $fieldUids = array_fill_keys(array_map(
            fn (CustomField $element) => $element->getFieldUid(),
            $this->getLayout()->getElementsByType(CustomField::class),
        ), true);

        foreach ($elements as $element) {
            if ($element instanceof CustomField) {
                $fieldUid = $element->getFieldUid();

                if (isset($fieldUids[$fieldUid]) && ! $element->isMultiInstance()) {
                    throw new InvalidArgumentException(sprintf(
                        'The field "%s" is already included in this field layout.',
                        $element->attribute(),
                    ));
                }

                $fieldUids[$fieldUid] = true;
            }
        }

        foreach ($elements as $element) {
            $element->dateAdded ??= now();
        }

        $this->setElements([...$this->getElements(), ...$elements]);

        return $this;
    }

    /** @param (Closure(CustomField): mixed)|null $configure */
    public function field(FieldInterface|string $field, ?Closure $configure = null): static
    {
        $element = CustomField::make($field);
        $configure?->__invoke($element);

        return $this->add($element);
    }

    /** @param (Closure(Heading): mixed)|null $configure */
    public function heading(string $heading, ?Closure $configure = null): static
    {
        $element = Heading::make($heading);
        $configure?->__invoke($element);

        return $this->add($element);
    }

    /** @param (Closure(Tip): mixed)|null $configure */
    public function tip(string $tip, ?Closure $configure = null): static
    {
        $element = Tip::make($tip);
        $configure?->__invoke($element);

        return $this->add($element);
    }

    /** @param (Closure(Tip): mixed)|null $configure */
    public function warning(string $warning, ?Closure $configure = null): static
    {
        $element = Tip::make($warning)->warning();
        $configure?->__invoke($element);

        return $this->add($element);
    }

    /** @param (Closure(Markdown): mixed)|null $configure */
    public function markdown(string $content, ?Closure $configure = null): static
    {
        $element = Markdown::make($content);
        $configure?->__invoke($element);

        return $this->add($element);
    }

    /** @param (Closure(Template): mixed)|null $configure */
    public function template(string $template, ?Closure $configure = null): static
    {
        $element = Template::make($template);
        $configure?->__invoke($element);

        return $this->add($element);
    }

    /** @param (Closure(HorizontalRule): mixed)|null $configure */
    public function horizontalRule(?Closure $configure = null): static
    {
        $element = HorizontalRule::make();
        $configure?->__invoke($element);

        return $this->add($element);
    }

    /** @param (Closure(LineBreak): mixed)|null $configure */
    public function lineBreak(?Closure $configure = null): static
    {
        $element = LineBreak::make();
        $configure?->__invoke($element);

        return $this->add($element);
    }

    public function getHtmlId(): string
    {
        $asciiName = isset($this->name) ? Str::kebab(Str::ascii($this->name)) : '';

        if ($asciiName === '') {
            // Use md5() as a fallback
            $asciiName = sprintf('tab-%s', md5((string) $this->name));
        }

        // ensure unique tab id even if there are multiple tabs with the same name
        $tabOrder = Str::padLeft((string) $this->sortOrder, 2, '0');

        return Html::id("tab$tabOrder-$asciiName");
    }

    /**
     * Returns whether the given element has any validation errors for the custom fields included in this tab.
     */
    public function elementHasErrors(ElementInterface $element): bool
    {
        if ($element->errors()->isEmpty()) {
            return false;
        }

        return array_any($this->getElements(), fn ($layoutElement) => $layoutElement instanceof BaseField && $element->errors()->has($layoutElement->attribute().'.*'));
    }
}
