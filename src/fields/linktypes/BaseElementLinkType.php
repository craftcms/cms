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
     * @return ElementInterface|string
     * @phpstan-return class-string<ElementInterface>
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
    public string|array|null $sources = '*';

    public function __construct($config = [])
    {
        if (array_key_exists('sources', $config) && empty($config['sources'])) {
            // Not possible to have no sources selected, so go with the default
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
        $availableSourceKeys = array_flip($this->availableSources());
        $sources = Collection::make(Craft::$app->getElementSources()->getSources(
            static::elementType(),
            ElementSources::CONTEXT_FIELD
        ))
            ->filter(fn(array $source) => (
                ($source['type'] === ElementSources::TYPE_NATIVE && isset($availableSourceKeys[$source['key']])) ||
                $source['type'] === ElementSources::TYPE_CUSTOM
            ))
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
            'values' => $this->sources,
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
        $elements = array_filter([$this->element($value)]);
        $id = sprintf('elementselect%s', mt_rand());

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($id, $refHandle) => <<<JS
(() => {
  const container = $('#' + $id);
  const input = container.next('input');
  const elementSelect = container.data('elementSelect');
  const refHandle = $refHandle;
  elementSelect.on('selectElements', (ev) => {
    const element = ev.elements[0];
    input.val(`{\${refHandle}:\${element.id}@\${element.siteId}:url}`);
  });
  elementSelect.on('removeElements', () => {
    input.val('');
  });
})();
JS, [
            'id' => $view->namespaceInputId($id),
            'refHandle' => static::elementType()::refHandle(),
        ]);

        return
            Cp::elementSelectHtml([
                'id' => $id,
                'elementType' => static::elementType(),
                'limit' => 1,
                'single' => true,
                'elements' => $elements,
                'sources' => $this->sources,
                'criteria' => $this->selectionCriteria(),
            ]) .
            Html::hiddenInput('value', $value);
    }

    /**
     * Returns an array of source keys for the element type, filtering out any sources that can’t be linked to.
     *
     * @return string[]
     */
    protected function availableSources(): array
    {
        return [];
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
