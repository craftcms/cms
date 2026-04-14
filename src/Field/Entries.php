<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use craft\base\ElementInterface;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Gql\Arguments\Elements\Entry as EntryArguments;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql as GqlService;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\GqlHelper as Gql;
use CraftCms\Cms\Gql\Interfaces\Elements\Entry as EntryInterface;
use CraftCms\Cms\Gql\Resolvers\Elements\Entry as EntryResolver;
use CraftCms\Cms\Support\Facades\ElementSources;
use CraftCms\Cms\Support\Facades\Sections;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Override;

use function CraftCms\Cms\t;

/**
 * Entries represents an Entries field.
 */
class Entries extends BaseRelationField
{
    /**
     * @var bool Whether to show input sources for sections the user doesn’t have permission to view
     */
    public bool $showUnpermittedSections = false;

    /**
     * @var bool Whether to show entries the user doesn’t have permission to view,
     *           per the “View other users’ entries” permission.
     */
    public bool $showUnpermittedEntries = false;

    #[Override]
    protected string $settingsTemplate = '_components/fieldtypes/Entries/settings.twig';

    #[Override]
    protected ?string $inputJsClass = 'Craft.EntrySelectInput';

    #[Override]
    public static function displayName(): string
    {
        return t('Entries');
    }

    #[Override]
    public static function icon(): string
    {
        return 'newspaper';
    }

    public static function elementType(): string
    {
        return Entry::class;
    }

    #[Override]
    public static function defaultSelectionLabel(): string
    {
        return t('Add an entry');
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', EntryQuery::class, ElementCollection::class, Entry::class);
    }

    public function __construct(array $config = [])
    {
        // Default showUnpermittedSections and showUnpermittedEntries to true for existing Entries fields
        if (isset($config['id']) && ! isset($config['showUnpermittedSections'])) {
            $config['showUnpermittedSections'] = true;
            $config['showUnpermittedEntries'] = true;
        }

        parent::__construct($config);
    }

    #[Override]
    protected function inputTemplateVariables(array|ElementQueryInterface|null $value = null, ?ElementInterface $element = null): array
    {
        $variables = parent::inputTemplateVariables($value, $element);

        if (! $this->hasSelectionCondition() && $this->showSearchInput($element)) {
            /** @var string[] $sources */
            $sources = $this->getInputSources($element);
            if (preg_match('/^section:(.+)$/', reset($sources), $matches)) {
                if ($section = Sections::getSectionByUid($matches[1])) {
                    $variables['jsSettings']['sectionId'] = $section->id;
                }
            }
        }

        return $variables;
    }

    #[Override]
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryEntries($schema);
    }

    #[Override]
    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(EntryInterface::getType())),
            'args' => [
                ...EntryArguments::getArguments(),
                ...$this->gqlFieldArguments(),
            ],
            'resolve' => EntryResolver::class.'::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    #[Override]
    public function getEagerLoadingGqlConditions(): ?array
    {
        $allowedEntities = Gql::extractAllowedEntitiesFromSchema();
        $sectionUids = array_flip($allowedEntities['sections'] ?? []);

        if (empty($sectionUids)) {
            return null;
        }

        $sectionIds = [];
        $entryTypeIds = [];

        foreach (Sections::getAllSections() as $section) {
            if (isset($sectionUids[$section->uid])) {
                $sectionIds[] = $section->id;
                array_push(
                    $entryTypeIds,
                    ...array_map(fn (EntryType $entryType) => $entryType->id, $section->getEntryTypes()),
                );
            }
        }

        return [
            'sectionId' => $sectionIds,
            'typeId' => array_unique($entryTypeIds),
        ];
    }

    #[Override]
    public function getInputSelectionCriteria(): array
    {
        $criteria = parent::getInputSelectionCriteria();

        if (! $this->showUnpermittedEntries) {
            $criteria['editable'] = true;
        }

        return $criteria;
    }

    protected function createSelectionCondition(): ElementCondition
    {
        $condition = Entry::createCondition();
        $condition->queryParams = ['section', 'sectionId'];

        return $condition;
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        $mockup = new Entry;
        $mockup->title = t('Related {type} Title', ['type' => $mockup->displayName()]);
        if ($this->sources === '*') {
            $section = Sections::getAllSections()->first();
        } else {
            $section = Sections::getSectionByUid(str_replace('section:', '', $this->sources[0]));
        }

        if (! $section) {
            // if we don't have a section, let's return a string, cause chipHtml will complain about not being able to get a type
            return $mockup->title.' - '.t('placeholder');
        }

        $mockup->sectionId = $section->id;

        return app(ElementHtml::class)->chipHtml($mockup);
    }

    #[Override]
    public function getInputSources(?ElementInterface $element = null): array|string|null
    {
        if ($this->sources === null) {
            return $this->sources;
        }

        // Enforce the showUnpermittedSections setting
        if (! $this->showUnpermittedSections) {
            // get all the native & custom sources that user has permissions to view
            $permittedSources = ElementSources::getSources(Entry::class)
                ->where('type', '!==', ElementSources::TYPE_HEADING)
                ->pluck('key')
                ->flip()
                ->all();

            // if the field is set to show all the sources
            if ($this->sources === '*') {
                // return all the native & custom sources that user has permissions to view
                return array_keys($permittedSources);
            }

            // otherwise, go through all the selected sources and return ones that user has permissions to view
            return Collection::make((array) $this->sources)
                ->filter(fn ($source) => isset($permittedSources[$source]))
                ->values()
                ->all();
        }

        return $this->sources;
    }
}
