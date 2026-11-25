<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\behaviors\EventBehavior;
use craft\db\FixedOrderExpression;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use craft\events\CancelableEvent;
use craft\gql\arguments\elements\Category as CategoryArguments;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\resolvers\elements\Category as CategoryResolver;
use craft\helpers\ElementHelper;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\models\GqlSchema;
use craft\services\ElementSources;
use craft\services\Gql as GqlService;
use GraphQL\Type\Definition\Type;

/**
 * Categories represents a Categories field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Categories extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Categories');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'sitemap';
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return Category::class;
    }

    /**
     * @inheritdoc
     */
    protected static function canShowSiteMenu(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add a category');
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', CategoryQuery::class, ElementCollection::class, Category::class);
    }

    /**
     * @inheritdoc
     */
    public bool $allowMultipleSources = false;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // allow categories to limit selection if `maintainHierarchy` isn't checked
        $config['allowLimit'] = true;

        // Default maintainHierarchy to true for existing Assets fields
        if (isset($config['id']) && !isset($config['maintainHierarchy'])) {
            $config['maintainHierarchy'] = true;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $query = parent::normalizeValue($value, $element);

        // Fill in gaps and enforce the branch limit, but only once this query actually gets
        // executed on its own - if it's about to be discarded in favor of eager-loaded results
        // (e.g. via `.eagerly()`), there's no point doing this work just to throw it away.
        if (is_array($value) && $this->maintainHierarchy && $query instanceof ElementQuery) {
            $query->attachBehavior(self::class, new EventBehavior([
                ElementQuery::EVENT_BEFORE_PREPARE => function(CancelableEvent $event, ElementQuery $query) use ($element) {
                    /** @var Category[] $categories */
                    $categories = Category::find()
                        ->siteId($this->targetSiteId($element))
                        ->id($query->where['elements.id'] ?? [])
                        ->status(null)
                        ->all();

                    $structuresService = Craft::$app->getStructures();
                    $structuresService->fillGapsInElements($categories);

                    if ($this->branchLimit) {
                        $structuresService->applyBranchLimitToElements($categories, $this->branchLimit);
                    }

                    $finalIds = array_map(fn(Category $category) => $category->id, $categories);
                    $query->where(['elements.id' => $finalIds]);
                    if (!empty($finalIds)) {
                        $query->orderBy([new FixedOrderExpression('elements.id', $finalIds, Craft::$app->getDb())]);
                    }
                },
            ]));
        }

        return $query;
    }

    /**
     * @inheritdoc
     *
     * Note: when Categories field is used across multiple field layouts (e.g. several entry types in
     * the same section), calling `.eagerly()` without an explicit alias on a loop of mixed-type
     * elements will re-trigger this method once per distinct field-layout provider, each time
     * recomputing results for every source element that has this field, not just its own type's
     * subset. (`ElementQuery`'s default eager-loading alias is derived from the *triggering*
     * element's own provider handle, even though eager-loading map resolution itself matches by
     * field ID across all providers.) Pass an explicit alias (`.eagerly('someAlias')`) in that
     * scenario so every element shares one dedup key and only the first trigger actually runs.
     */
    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        $map = parent::getEagerLoadingMap($sourceElements);

        // if we're not maintaining hierarchy, go with the default behavior
        if (!$this->maintainHierarchy || !is_array($map) || empty($map['map'])) {
            return $map;
        }

        // array keyed by the sourceId with value containing all its target IDs
        $targetIdsBySource = [];
        foreach ($map['map'] as $mapping) {
            $targetIdsBySource[$mapping['source']][] = $mapping['target'];
        }

        // Fetch every referenced category in one batched query, rather than per source element
        // (this allows us to lower the number of queries needed to complete the task)
        $allTargetIds = array_values(array_unique(array_merge(...array_values($targetIdsBySource))));
        /** @var Category[] $categoriesById */
        $categoriesById = Category::find()
            ->siteId($sourceElements[0]->siteId)
            ->id($allTargetIds)
            ->status(null)
            ->indexBy('id')
            ->all();

        $structuresService = Craft::$app->getStructures();
        $newMap = [];

        // now for each source, fill the gaps and apply branch limit
        foreach ($targetIdsBySource as $sourceId => $targetIds) {
            $categories = array_values(array_filter(array_map(
                fn($id) => $categoriesById[$id] ?? null,
                $targetIds,
            )));

            // Fill in any gaps
            $structuresService->fillGapsInElements($categories);

            // Enforce the branch limit
            if ($this->branchLimit) {
                $structuresService->applyBranchLimitToElements($categories, $this->branchLimit);
            }

            foreach ($categories as $category) {
                $newMap[] = ['source' => $sourceId, 'target' => $category->id];
            }
        }

        // update the map
        $map['map'] = $newMap;

        /** @phpstan-ignore-next-line */
        return $map;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        // Make sure the field is set to a valid category group
        if ($this->source) {
            $source = ElementHelper::findSource(static::elementType(), $this->source, ElementSources::CONTEXT_FIELD);
        }

        if (empty($source)) {
            return '<p class="error">' . Craft::t('app', 'This field is not set to a valid category group.') . '</p>';
        }

        return parent::inputHtml($value, $element, $inline);
    }

    /**
     * @inheritdoc
     */
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryCategories($schema);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(CategoryInterface::getType())),
            'args' => CategoryArguments::getArguments(),
            'resolve' => CategoryResolver::class . '::resolve',
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
        $categoryGroupUids = $allowedEntities['categorygroups'] ?? [];

        if (empty($categoryGroupUids)) {
            return null;
        }

        $categoriesService = Craft::$app->getCategories();
        $groupIds = array_filter(array_map(function(string $uid) use ($categoriesService) {
            $group = $categoriesService->getGroupByUid($uid);
            return $group->id ?? null;
        }, $categoryGroupUids));

        return [
            'groupId' => $groupIds,
        ];
    }
}
