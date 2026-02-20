<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\errors\SiteNotFoundException;
use craft\fields\Link;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\services\ElementSources;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use yii\base\InvalidArgumentException;

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
     * Returns the GraphQL type that elements of this type should
     *
     * @return Type
     * @since 5.7.0
     */
    public static function elementGqlType(): Type
    {
        return static::elementType()::baseGqlType();
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
    field.updateFilename(element.\$element.data('filename'));
  });
  elementSelect.on('removeElements', () => {
    input.val('');
    field.updateLabel('');
    field.updateFilename('');
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

    /**
     * @inheritdoc
     */
    public function validateValue(string $value, ?string &$error = null): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isValueEmpty(string $value): bool
    {
        // check if the element we're linking to still exists (e.g. it wasn't deleted)
        // we already validated the link type, so getting the element type as string
        // (instead of getting all element types ref handles) should be fine
        preg_match("/^{(?P<elementType>[\w\\\\]+):(?P<elementId>\d+)(?:@(?P<siteId>\d+))?/", $value, $matches);

        // if we couldn't get an element ID, treat the value as not empty
        // as we already checked for empty string, null and empty array in base\Field::isValueEmpty()
        if (empty($matches['elementId'])) {
            return false;
        }

        /** @var class-string<ElementInterface>|null $elementType */
        $elementType = Craft::$app->getElements()->getElementTypeByRefHandle($matches['elementType']);
        if (!$elementType) {
            return true;
        }

        return !$elementType::find()
            ->id($matches['elementId'])
            ->siteId($matches['siteId'] ?? null)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->exists();
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(ElementInterface|int|string $value): string
    {
        if ($value instanceof ElementInterface) {
            if (!is_a($value, static::elementType())) {
                throw new InvalidArgumentException(sprintf('$value must be an %s instance, ID, or reference tag.', static::elementType()::lowerDisplayName()));
            }
            $value = sprintf('{%s:%s@%s:url}',
                static::elementType()::refHandle(),
                $value->id,
                $value->siteId,
            );
        }
        if (is_numeric($value)) {
            $value = sprintf('{%s:%s@%s:url}',
                static::elementType()::refHandle(),
                $value,
                Craft::$app->getSites()->getCurrentSite()->id,
            );
        }

        return parent::normalizeValue($value);
    }

    /**
     * Returns an element query that will fetch the element the field is supposed to link to.
     *
     * @param string|null $value
     * @return ElementQueryInterface|null
     * @since 5.6.0
     */
    public function elementQuery(?string $value): ?ElementQueryInterface
    {
        if (
            !$value ||
            !preg_match(sprintf('/^\{%s:(\d+)(?:@(\d+))?:url\}$/', static::elementType()::refHandle()), $value, $match)
        ) {
            return null;
        }

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

        return $query;
    }

    /**
     * Returns an Element that the field is supposed to link to.
     *
     * @param string|null $value
     * @return ElementInterface|null
     * @throws SiteNotFoundException
     */
    public function element(?string $value): ?ElementInterface
    {
        if (!isset(self::$fetchedElements[$value])) {
            $query = $this->elementQuery($value);

            if (!$query) {
                return null;
            }

            self::$fetchedElements[$value] = $query->one() ?? false;
        }

        return self::$fetchedElements[$value] ?: null;
    }
}
