<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\elements\ElementCollection;
use craft\gql\arguments\elements\Category as CategoryArguments;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\resolvers\elements\Category as CategoryResolver;
use craft\helpers\ArrayHelper;
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
    public static function elementType(): string
    {
        return Category::class;
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
    public static function valueType(): string
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
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (is_array($value) && $this->maintainHierarchy) {
            /** @var Category[] $categories */
            $categories = Category::find()
                ->siteId($this->targetSiteId($element))
                ->id(array_values(array_filter($value)))
                ->status(null)
                ->all();

            // Fill in any gaps
            $structuresService = Craft::$app->getStructures();
            $structuresService->fillGapsInElements($categories);

            // Enforce the branch limit
            if ($this->branchLimit) {
                $structuresService->applyBranchLimitToElements($categories, $this->branchLimit);
            }

            $value = ArrayHelper::getColumn($categories, 'id');
        }

        return parent::normalizeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        // Make sure the field is set to a valid category group
        if ($this->source) {
            $source = ElementHelper::findSource(static::elementType(), $this->source, ElementSources::CONTEXT_FIELD);
        }

        if (empty($source)) {
            return '<p class="error">' . Craft::t('app', 'This field is not set to a valid category group.') . '</p>';
        }

        return parent::inputHtml($value, $element);
    }

    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        $map = parent::getEagerLoadingMap($sourceElements);
        $map['criteria']['orderBy'] = ['structureelements.lft' => SORT_ASC];
        return $map;
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
