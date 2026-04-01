<?php

declare(strict_types=1);

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\LegacyEventConstants;
use craft\elements\Category;
use craft\elements\db\CategoryQuery;
use craft\gql\arguments\elements\Category as CategoryArguments;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\resolvers\elements\Category as CategoryResolver;
use craft\helpers\Gql;
use craft\helpers\Gql as GqlHelper;
use craft\services\Gql as GqlService;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Support\Facades\Structures;
use GraphQL\Type\Definition\Type;

use Override;
use function CraftCms\Cms\t;

/**
 * Categories represents a Categories field.
 *
 * @deprecated in 6.0.0
 */
class Categories extends \CraftCms\Cms\Field\BaseRelationField
{
    use LegacyEventConstants;

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Categories');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function icon(): string
    {
        return 'sitemap';
    }

    /**
     * {@inheritdoc}
     */
    public static function elementType(): string
    {
        return Category::class;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected static function canShowSiteMenu(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function defaultSelectionLabel(): string
    {
        return t('Add a category', category: 'yii2-adapter');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', CategoryQuery::class, ElementCollection::class, Category::class);
    }

    /**
     * {@inheritdoc}
     */
    public bool $allowMultipleSources = false;

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (is_array($value) && $this->maintainHierarchy) {
            /** @var Category[] $categories */
            $categories = Category::find()
                ->siteId($this->targetSiteId($element))
                ->id(array_values(array_filter($value)))
                ->status(null)
                ->all();

            // Fill in any gaps
            Structures::fillGapsInElements($categories);

            // Enforce the branch limit
            if ($this->branchLimit) {
                Structures::applyBranchLimitToElements($categories, $this->branchLimit);
            }

            $value = array_map(fn(Category $category) => $category->id, $categories);
        }

        return parent::normalizeValue($value, $element);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        // Make sure the field is set to a valid category group
        if ($this->source) {
            $source = app(ElementSources::class)->findSource(self::elementType(), $this->source, ElementSources::CONTEXT_FIELD);
        }

        if (empty($source)) {
            return '<p class="error">' . t('This field is not set to a valid category group.', category: 'yii2-adapter') . '</p>';
        }

        return parent::inputHtml($value, $element, $inline);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryCategories($schema);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getContentGqlType(): array
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
     * {@inheritdoc}
     */
    #[Override]
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
