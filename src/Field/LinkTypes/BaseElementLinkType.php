<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\LinkTypes;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * Base element link type.
 */
abstract class BaseElementLinkType extends BaseLinkType
{
    /**
     * @var array<string,ElementInterface|false>
     *
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

    #[Override]
    public static function displayName(): string
    {
        return static::elementType()::displayName();
    }

    /**
     * Returns the GraphQL type that elements of this type should
     *
     * @since 5.7.0
     */
    public static function elementGqlType(): Type
    {
        return static::elementType()::baseGqlType();
    }

    /** @var list<string>|null The element sources elements can be linked from */
    public ?array $sources = null;

    /** @param array<string, bool|list<string>|null> $config */
    public function __construct($config = [])
    {
        if (
            isset($config['sources']) &&
            (! is_array($config['sources']) || empty($config['sources']) || $config['sources'] === ['*'])
        ) {
            unset($config['sources']);
        }

        parent::__construct($config);
    }

    public function supports(string $value): bool
    {
        return (bool) preg_match(sprintf('/^\{%s:(\d+)(@(\d+))?:url\}$/', static::elementType()::refHandle()), $value);
    }

    #[Override]
    public function settingsNodes(string $prefix): array
    {
        $sources = Collection::make($this->availableSources())
            ->map(fn (array $source): array => [
                'label' => (string) $source['label'],
                'value' => $source['key'],
            ])
            ->values()
            ->all();

        if ($sources === []) {
            return [];
        }

        array_unshift($sources, ['label' => t('All'), 'value' => '*']);

        return [
            FormField::make(t('{type} Sources', ['type' => static::elementType()::displayName()]))
                ->control(Choice::make($this->settingPath($prefix, 'sources'))
                    ->multiple()
                    ->presentation(ChoicePresentation::Checkboxes)
                    ->options($sources)
                    ->value($this->sources ?? ['*'])),
        ];
    }

    #[Override]
    public function renderValue(string $value): string
    {
        return $this->element($value)?->getUrl() ?? '';
    }

    public function linkLabel(string $value): string
    {
        $element = $this->element($value);

        return $element ? (string) $element : '';
    }

    public function inputHtml(Link $field, ?string $value, string $containerId): string
    {
        $id = sprintf('elementselect%s', mt_rand());

        HtmlStack::jsWithVars(fn ($id, $refHandle) => <<<JS
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
            'id' => InputNamespace::namespaceId($id),
            'refHandle' => static::elementType()::refHandle(),
        ]);

        return
            FormFields::elementSelectHtml(array_merge($this->elementSelectConfig(), [
                'id' => $id,
                'elements' => array_filter([$this->element($value)]),
                'showSiteMenu' => true,
                'modalSettings' => [
                    'matchSiteBeforeDisablingElement' => true,
                    'siteId' => app(RequestedSite::class)->get()?->id,
                ],
            ])).
            Html::hiddenInput('value', $value);
    }

    /**
     * Returns all sources available to the field, based on
     * [[availableSources()]] plus any custom sources for the element type.
     *
     * @return array<int, array{key:string, label:string, type:string}>
     */
    protected function availableSources(): array
    {
        $availableSourceKeys = array_flip($this->availableSourceKeys());

        return ElementSources::getSources(
            static::elementType(),
            ElementSources::CONTEXT_FIELD,
        )->filter(fn (array $source) => (
            ($source['type'] === ElementSources::TYPE_NATIVE && isset($availableSourceKeys[$source['key']])) ||
            $source['type'] === ElementSources::TYPE_CUSTOM
        ))
            ->all();
    }

    /**
     * Returns an array of source keys for the element type, filtering out any sources that can’t be linked to.
     *
     * @return list<string>
     */
    protected function availableSourceKeys(): array
    {
        return [];
    }

    /**
     * Returns the config array that will be passed to [[\CraftCms\Cms\Cp\FormFields::elementSelectHtml()]].
     *
     * @return array{
     *     elementType: class-string<ElementInterface>,
     *     limit: int,
     *     single: bool,
     *     sources: string|array<int, string>,
     *     criteria: array<string, bool|list<string>|string|null>,
     * }
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

    /** @return array<string, bool|list<string>|string|null> */
    protected function selectionCriteria(): array
    {
        return [
            'uri' => 'not :empty:',
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     kind: string,
     *     elementType: class-string<ElementInterface>,
     *     refHandle: string,
     *     elementSelectConfig: array{
     *         elementType: class-string<ElementInterface>,
     *         limit: int,
     *         single: bool,
     *         sources: string|array<int, string>,
     *         criteria: array<string, bool|list<string>|string|null>,
     *     },
     * }
     */
    #[Override]
    public function pickerConfig(): array
    {
        return array_merge(parent::pickerConfig(), [
            'kind' => 'element',
            'elementType' => static::elementType(),
            'refHandle' => static::elementType()::refHandle(),
            'elementSelectConfig' => $this->elementSelectConfig(),
        ]);
    }

    public function validateValue(string $value, ?string &$error = null): bool
    {
        return true;
    }

    #[Override]
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
        $elementType = Elements::getElementTypeByRefHandle($matches['elementType']);
        if (! $elementType) {
            return true;
        }

        return ! $elementType::find()
            ->id($matches['elementId'])
            ->siteId($matches['siteId'] ?? null)
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->revisions(null)
            ->exists();
    }

    #[Override]
    public function normalizeValue(ElementInterface|int|string $value): string
    {
        if ($value instanceof ElementInterface) {
            if (! is_a($value, static::elementType())) {
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
                Sites::getCurrentSite()->id,
            );
        }

        return parent::normalizeValue($value);
    }

    /**
     * Returns an element query that will fetch the element the field is supposed to link to.
     *
     * @since 5.6.0
     */
    public function elementQuery(?string $value): ?ElementQueryInterface
    {
        if (
            ! $value ||
            ! preg_match(sprintf('/^\{%s:(\d+)(?:@(\d+))?:url\}$/', static::elementType()::refHandle()), $value, $match)
        ) {
            return null;
        }

        $id = $match[1];
        $siteId = $match[2] ?? null;

        $query = static::elementType()::find()
            ->id((int) $id)
            ->status(null)
            ->drafts(null)
            ->revisions(null);

        if ($siteId) {
            $query->siteId((int) $siteId);
        } else {
            $query
                ->site('*')
                ->unique()
                ->preferSites([Sites::getCurrentSite()->id]);
        }

        return $query;
    }

    /**
     * Returns an Element that the field is supposed to link to.
     *
     * @throws SiteNotFoundException
     */
    public function element(?string $value): ?ElementInterface
    {
        if (! isset(self::$fetchedElements[$value])) {
            $query = $this->elementQuery($value);

            if (! $query) {
                return null;
            }

            self::$fetchedElements[$value] = $query->one() ?? false;
        }

        return self::$fetchedElements[$value] ?: null;
    }
}
