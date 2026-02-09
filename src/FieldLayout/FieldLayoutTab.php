<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use craft\base\ElementInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Field\Exceptions\FieldNotFoundException;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Log;
use Override;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

/**
 * FieldLayoutTab model class.
 *
 * @property FieldLayoutElement[]|null $elements The tab’s layout elements
 * @property FieldLayout|null $layout The tab’s layout
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
class FieldLayoutTab extends FieldLayoutComponent
{
    /**
     * Creates a new field layout tab from the given config.
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
            ($this->hasConditions() ? Html::tag('div', Cp::iconSvg('diamond'), [
                'class' => array_filter(array_merge(['cp-icon', 'puny', 'orange'])),
                'title' => t('This tab is conditional'),
                'aria' => ['label' => t('This tab is conditional')],
            ]) : '');
    }

    /**
     * Updates a field layout tab’s config to the new format.
     */
    public static function updateConfig(array &$config): void
    {
        if (! array_key_exists('fields', $config)) {
            return;
        }

        $config['elements'] = [];

        $config['fields'] = Arr::sort($config['fields'], 'sortOrder');
        foreach ($config['fields'] as $fieldUid => $fieldConfig) {
            $config['elements'][] = [
                'type' => CustomField::class,
                'fieldUid' => $fieldUid,
                'required' => (bool) $fieldConfig['required'],
            ];
        }

        unset($config['fields']);
    }

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

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('elements', $config)) {
            if (is_string($config['elements'])) {
                $config['elements'] = Json::decode($config['elements']);
            }
            if (! is_array($config['elements'])) {
                unset($config['elements']);
            }
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['sortOrder']);

        return $fields;
    }

    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'id' => ['nullable', 'integer'],
            'layoutId' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'sortOrder' => ['nullable', 'string', 'max:4'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function hasSettings(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function settingsHtml(): ?string
    {
        return Cp::textFieldHtml([
            'label' => t('Name'),
            'name' => 'name',
            'value' => $this->name,
            'required' => true,
        ]);
    }

    /**
     * Returns the field layout tab’s config.
     */
    public function getConfig(): array
    {
        if (! isset($this->uid)) {
            $this->uid = Str::uuid()->toString();
        }

        $config = Arr::only($this->getAttributes(), ['name', 'uid', 'userCondition', 'elementCondition']);
        $config['elements'] = $this->getElementConfigs();

        return $config;
    }

    /**
     * Returns the tab’s elements’ configs.
     *
     * @return array[]
     */
    public function getElementConfigs(): array
    {
        $elementConfigs = [];

        foreach ($this->getElements() as $layoutElement) {
            if (! isset($layoutElement->uid)) {
                $layoutElement->uid = Str::uuid()->toString();
            }
            $elementConfigs[] = ['type' => $layoutElement::class] + $layoutElement->getAttributes();
        }

        return $elementConfigs;
    }

    /**
     * Returns the tab’s layout.
     *
     * @return FieldLayout The tab’s layout.
     *
     * @throws InvalidConfigException if [[layoutId]] is set but invalid
     */
    #[Override]
    public function getLayout(): FieldLayout
    {
        if (isset($this->_layout)) {
            return $this->_layout;
        }

        if (! $this->layoutId) {
            throw new InvalidConfigException('Field layout tab is missing its field layout.');
        }

        if (($this->_layout = app(Fields::class)->getLayoutById($this->layoutId)) === null) {
            throw new InvalidConfigException('Invalid layout ID: '.$this->layoutId);
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
        return $this->_elements ?? [];
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
                    /** @phpstan-ignore catch.neverThrown */
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

    /**
     * Returns the tab’s HTML ID.
     */
    public function getHtmlId(): string
    {
        $asciiName = isset($this->name) ? Str::kebab(Str::ascii($this->name, 'en')) : '';

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
