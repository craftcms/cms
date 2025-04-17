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
use craft\services\Gql as GqlService;
use GraphQL\Type\Definition\Type;

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
     * @inerhitdoc
     */
    public function getInputSources(?ElementInterface $element = null): array|string|null
    {
        if ($this->sources === null) {
            return $this->sources;
        }

        // Enforce the showUnpermittedSections setting
        if (!$this->showUnpermittedSections) {
            $userService = Craft::$app->getUser();
            return ArrayHelper::where((array)$this->sources, function(string $source) use ($userService) {
                // If it’s not a section, let it through
                if (!str_starts_with($source, 'section:')) {
                    return true;
                }
                // Only show it if they have permission to view it
                $sectionUid = explode(':', $source)[1];
                return $userService->checkPermission("viewEntries:$sectionUid");
            }, true, true, false);
        }
        return $this->sources;
    }
}
