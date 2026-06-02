<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementCondition;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\EntryType;
use craft\models\GqlSchema;
use craft\services\ElementSources;
use craft\services\Gql as GqlService;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;

/**
 * Entries represents an Entries field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Entries extends BaseRelationField
{
    /**
     * @var bool Whether to show input sources for sections the user doesn’t have permission to view
     * @since 5.7.0
     */
    public bool $showUnpermittedSections = false;

    /**
     * @var bool Whether to show entries the user doesn’t have permission to view,
     * per the “View other users’ entries” permission.
     * @since 5.7.0
     */
    public bool $showUnpermittedEntries = false;

    /**
     * @inheritdoc
     */
    protected string $settingsTemplate = '_components/fieldtypes/Entries/settings.twig';

    /**
     * @inheritdoc
     */
    protected ?string $inputJsClass = 'Craft.EntrySelectInput';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Entries');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'newspaper';
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return Entry::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an entry');
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', EntryQuery::class, ElementCollection::class, Entry::class);
    }

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Default showUnpermittedSections and showUnpermittedEntries to true for existing Entries fields
        if (isset($config['id']) && !isset($config['showUnpermittedSections'])) {
            $config['showUnpermittedSections'] = true;
            $config['showUnpermittedEntries'] = true;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function inputTemplateVariables(array|ElementQueryInterface $value = null, ?ElementInterface $element = null): array
    {
        $variables = parent::inputTemplateVariables($value, $element);

        if (!$this->hasSelectionCondition() && $this->showSearchInput($element)) {
            /** @var string[] $sources */
            $sources = $this->getInputSources($element);
            if (preg_match('/^section:(.+)$/', reset($sources), $matches)) {
                $section = Craft::$app->getEntries()->getSectionByUid($matches[1]);
                if ($section) {
                    $variables['jsSettings']['sectionId'] = $section->id;
                }
            }
        }

        return $variables;
    }

    /**
     * @inheritdoc
     */
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryEntries($schema);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(EntryInterface::getType())),
            'args' => [
                ...EntryArguments::getArguments(),
                ...$this->gqlFieldArguments(),
            ],
            'resolve' => EntryResolver::class . '::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getEagerLoadingGqlConditions(): ?array
    {
        $allowedEntities = Gql::extractAllowedEntitiesFromSchema();
        $sectionUids = array_flip($allowedEntities['sections'] ?? []);

        if (empty($sectionUids)) {
            return null;
        }

        $sectionIds = [];
        $entryTypeIds = [];

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            if (isset($sectionUids[$section->uid])) {
                $sectionIds[] = $section->id;
                array_push(
                    $entryTypeIds,
                    ...array_map(fn(EntryType $entryType) => $entryType->id, $section->getEntryTypes()),
                );
            }
        }

        return [
            'sectionId' => $sectionIds,
            'typeId' => array_unique($entryTypeIds),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getInputSelectionCriteria(): array
    {
        $criteria = parent::getInputSelectionCriteria();

        if (!$this->showUnpermittedEntries) {
            $criteria['editable'] = true;
        }

        return $criteria;
    }

    /**
     * @inheritdoc
     */
    protected function createSelectionCondition(): ?ElementCondition
    {
        $condition = Entry::createCondition();
        $condition->queryParams = ['section', 'sectionId'];
        return $condition;
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        $mockup = new Entry();
        $mockup->title = Craft::t('app', 'Related {type} Title', ['type' => $mockup->displayName()]);
        if ($this->sources == '*') {
            $section = Craft::$app->getEntries()->getAllSections()[0];
        } else {
            $section = Craft::$app->getEntries()->getSectionByUid(str_replace('section:', '', $this->sources[0]));
        }

        if (!$section) {
            // if we don't have a section, let's return a string, cause chipHtml will complain about not being able to get a type
            return $mockup->title . ' - ' . Craft::t('app', 'placeholder');
        }

        $mockup->sectionId = $section->id;

        return Cp::chipHtml($mockup);
    }

    /**
     * @inheritdoc
     */
    public function getInputSources(?ElementInterface $element = null): array|string|null
    {
        if ($this->sources === null) {
            return $this->sources;
        }

        // Enforce the showUnpermittedSections setting
        if (!$this->showUnpermittedSections) {
            // get all the native & custom sources that user has permissions to view
            $permittedSources = Collection::make(Craft::$app->getElementSources()->getSources(Entry::class))
                ->filter(fn($source) => $source['type'] !== ElementSources::TYPE_HEADING)
                ->pluck('key')
                ->flip()
                ->all();

            // if the field is set to show all the sources
            if ($this->sources === '*') {
                // return all the native & custom sources that user has permissions to view
                return array_keys($permittedSources);
            }

            // otherwise, go through all the selected sources and return ones that user has permissions to view
            return ArrayHelper::where((array)$this->sources,
                fn(string $sourceKey) => isset($permittedSources[$sourceKey]),
                true, true, false);
        }

        return $this->sources;
    }
}
