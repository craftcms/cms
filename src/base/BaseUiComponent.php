<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\events\ComponentPreRenderEvent;
use craft\helpers\ArrayHelper;
use craft\ui\attributes\AsTwigComponent;
use craft\ui\ComponentAttributes;
use Illuminate\Support\Collection;
use Twig\Extension\EscaperExtension;
use yii\base\InvalidConfigException;

abstract class BaseUiComponent extends Component implements UiComponentInterface
{
    /**
     * Default template path for components. Run through `sprintf` with the
     * name of the component.
     */
    public const TEMPLATE_PATTERN = '_ui/%s.twig';

    /**
     * @event PreRenderEvent The event fired before a UI component is rendered.
     */
    public const EVENT_COMPONENT_BEFORE_RENDER = 'beforeRenderComponent';

    /**
     * Variant of the button.
     *
     * @var string
     */
    public string $variant = 'default';

    /**
     * Set the variant of the button. Should be one of `default`, `submit` or `dashed`
     *
     * @param string $value
     * @return $this
     */
    public function variant(string $value = 'default'): static
    {
        $this->variant = $value;
        return $this;
    }

    /**
     * State of the button.
     *
     * @var string
     */
    public string $state = 'idle';

    /**
     * Set the state of the button. $value should be one of 'idle', 'loading', or 'disabled'
     *
     * @param string $value
     * @return $this
     */
    public function state(string $value = 'idle'): static
    {
        $this->state = $value;
        return $this;
    }

    /**
     * @var bool
     */
    public bool $debug = false;

    /**
     * Debug
     *
     * @param bool $value
     * @return $this
     */
    public function debug(bool $value = true): static
    {
        $this->debug = $value;
        return $this;
    }


    /**
     * Anything not consumed by properties will end up here.
     *
     * @var ComponentAttributes|null Collection of leftover attributes to be used as html attributes.
     */
    protected ?ComponentAttributes $htmlAttributes = null;

    final public function __construct(array $data = [], $config = [])
    {
        $this->mount($data);
        parent::__construct($config);
    }

    /**
     * Has the safe twig class been registered?
     *
     * @var bool
     */
    private bool $safeClassesRegistered = false;

    public static function create(array $props = []): static
    {
        return new static($props);
    }

    public function addClass(string $value): void
    {
        $this->htmlAttributes->addClass($value);
    }

    /**
     * Get component data defined in the attribute class.
     *
     * @return AsTwigComponent
     * @throws InvalidConfigException
     */
    protected function getMetadata(): AsTwigComponent
    {
        $reflectionClass = new \ReflectionClass(static::class);
        $attributes = $reflectionClass->getAttributes(AsTwigComponent::class);
        if (empty($attributes)) {
            throw new InvalidConfigException(static::class . " is missing `#[AsTwigComponent()]` attribute");
        }
        /** @var AsTwigComponent $attribute */
        $attribute = $attributes[0]->newInstance();

        return $attribute;
    }

    /**
     * Mount the component.
     *
     * Mounting the component is the process of mapping data passed in
     * onto the class and cleaning up the data. This should only happen
     * once.
     *
     * @param array $data
     * @return void
     * @throws InvalidConfigException
     */
    public function mount(array $data = []): void
    {
        $props = array_intersect_key($data, $this->getAttributes());
        $leftovers = array_diff_key($data, $this->getAttributes());

        // TODO: Is this safe enough?
        $this->setAttributes($props, false);

        /**
         * Did the user explicitly pass in html attributes?
         * If so, make sure those are mapped to the `htmlAttributes` property
         */
        $attributesVar = $this->getMetadata()->attributesVar;
        $attributes = $leftovers[$attributesVar] ?? [];
        unset($leftovers[$attributesVar]);

        $this->htmlAttributes = new ComponentAttributes(ArrayHelper::merge($leftovers, $attributes));
    }

    protected function prepare(): void
    {
    }

    /**
     * Prepare variables for rendering.
     *
     * @return array
     */
    protected function prepareVariables(): array
    {
        // Give components a chance to resolve attributes for twig
        $this->prepare();

        $context = ArrayHelper::merge(
            $this->getAttributes(),
            ['this' => $this],
            ['errors' => $this->getErrors()],
            ['attributes' => $this->htmlAttributes],
        );


        return $context;
    }

    public function render(): string
    {
        $this->validate();

        // Register the safe class against our attributes so we can output them without the raw filter
        // TODO: better place for this?
        if (!$this->safeClassesRegistered) {
            $twig = Craft::$app->getView()->getTwig();
            $twig->getExtension(EscaperExtension::class)->addSafeClass(ComponentAttributes::class, ['html']);

            $this->safeClassesRegistered = true;
        }

        $metdata = $this->getMetadata();
        $template = $metdata->template ?? sprintf(self::TEMPLATE_PATTERN, str_replace(':', '/', $metdata->name));

        $event = new ComponentPreRenderEvent([
            'template' => $template,
            'variables' => $this->prepareVariables(),
        ]);

        $this->trigger(self::EVENT_COMPONENT_BEFORE_RENDER, $event);

        return Craft::$app->getView()->renderTemplate($event->template, $event->variables);
    }
}
