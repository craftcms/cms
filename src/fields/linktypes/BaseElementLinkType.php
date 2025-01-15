<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\base\ElementInterface;
use craft\fields\Link;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\services\ElementSources;
use Illuminate\Support\Collection;

/**
 * Base element link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
abstract class BaseElementLinkType extends BaseLinkType
{
    /**
     * @var array<string,ElementInterface|false>
     * @see element()
     */
    private static array $fetchedElements = [];

    /**
     * Returns the element type this link type is for.
     *
     * @return class-string<ElementInterface>
     */
    abstract protected static function elementType(): string;

    public static function id(): string
    {
        return static::elementType()::refHandle();
    }

    public static function displayName(): string
    {
        return static::elementType()::displayName();
    }

    /**
     * @return string|string[] The element sources elements can be linked from
     */
    public ?array $sources = null;

    public function __construct($config = [])
    {
        if (
            isset($config['sources']) &&
            (!is_array($config['sources']) || empty($config['sources']) || $config['sources'] === ['*'])
        ) {
            unset($config['sources']);
        }

        parent::__construct($config);
    }

    public function getSettingsHtml(): ?string
    {
        return $this->sourcesSettingHtml();
    }

    /**
     * Returns the HTML for the “Sources” setting
     * @return string|null
     */
    protected function sourcesSettingHtml(): ?string
    {
        $sources = Collection::make($this->availableSources())
            ->keyBy(fn(array $source) => $source['key'])
            ->map(fn(array $source) => $source['label']);

        if ($sources->isEmpty()) {
            return null;
        }

        return Cp::checkboxSelectFieldHtml([
            'label' => Craft::t('app', '{type} Sources', [
                'type' => static::elementType()::displayName(),
            ]),
            'name' => 'sources',
            'options' => $sources->all(),
            'values' => $this->sources ?? '*',
            'showAllOption' => true,
        ]);
    }

    public function supports(string $value): bool
    {
        return (bool)preg_match(sprintf('/^\{%s:(\d+)(@(\d+))?:url\}$/', static::elementType()::refHandle()), $value);
    }

    public function renderValue(string $value): string
    {
        return $this->element($value)?->getUrl() ?? '';
    }

    public function linkLabel(string $value): string
    {
        $element = $this->element($value);
        return $element ? (string)$element : '';
    }

    public function inputHtml(Link $field, ?string $value, string $containerId): string
    {
        $id = sprintf('elementselect%s', mt_rand());

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($id, $refHandle) => <<<JS
(() => {
  const container = $('#' + $id);
  const field = container.closest('[data-link-field]').parent().data('linkField');
  const input = container.next('input');
  const elementSelect = container.data('elementSelect');
  const refHandle = $refHandle;
  elementSelect.on('selectElements', (ev) => {
    const element = ev.elements[0];
    input.val(`{\${refHandle}:\${element.id}@\${element.siteId}:url}`);
    field.updateLabel(element.label);
  });
  elementSelect.on('removeElements', () => {
    input.val('');
    field.updateLabel('');
  });
})();
JS, [
            'id' => $view->namespaceInputId($id),
            'refHandle' => static::elementType()::refHandle(),
        ]);

        return
            Cp::elementSelectHtml(array_merge($this->elementSelectConfig(), [
                'id' => $id,
                'elements' => array_filter([$this->element($value)]),
                'showSiteMenu' => true,
            ])) .
            Html::hiddenInput('value', $value);
    }

    /**
     * Returns all sources available to the field, based on
     * [[availableSources()]] plus any custom sources for the element type.
     *
     * @return array
     */
    protected function availableSources(): array
    {
        $availableSourceKeys = array_flip($this->availableSourceKeys());
        return Collection::make(Craft::$app->getElementSources()->getSources(
            static::elementType(),
            ElementSources::CONTEXT_FIELD,
        ))
            ->filter(fn(array $source) => (
                ($source['type'] === ElementSources::TYPE_NATIVE && isset($availableSourceKeys[$source['key']])) ||
                $source['type'] === ElementSources::TYPE_CUSTOM
            ))
            ->all();
    }

    /**
     * Returns an array of source keys for the element type, filtering out any sources that can’t be linked to.
     *
     * @return string[]
     */
    protected function availableSourceKeys(): array
    {
        return [];
    }

    /**
     * Returns the config array that will be passed to [[Cp::elementSelectHtml()]].
     *
     * @return array
     */
    protected function elementSelectConfig(): array
    {
        return [
            'elementType' => static::elementType(),
            'limit' => 1,
            'single' => true,
            'sources' => $this->sources ?? '*',
            'criteria' => $this->selectionCriteria(),
        ];
    }

    protected function selectionCriteria(): array
    {
        return [
            'uri' => 'not :empty:',
        ];
    }

    public function validateValue(string $value, ?string &$error = null): bool
    {
        return true;
    }

    public function element(?string $value): ?ElementInterface
    {
        if (
            !$value ||
            !preg_match(sprintf('/^\{%s:(\d+)(?:@(\d+))?:url\}$/', static::elementType()::refHandle()), $value, $match)
        ) {
            return null;
        }

        if (!isset(self::$fetchedElements[$value])) {
            $id = $match[1];
            $siteId = $match[2] ?? null;

            $query = static::elementType()::find()
                ->id((int)$id)
                ->status(null)
                ->drafts(null)
                ->revisions(null);

            if ($siteId) {
                $query->siteId((int)$siteId);
            } else {
                $query
                    ->site('*')
                    ->unique()
                    ->preferSites([Craft::$app->getSites()->getCurrentSite()->id]);
            }

            self::$fetchedElements[$value] = $query->one() ?? false;
        }

        return self::$fetchedElements[$value] ?: null;
    }
}
