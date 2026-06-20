<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Element\Queries;

use Craft;
use craft\elements\Category;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Yii2Adapter\Database\DeprecatedTable;
use CraftCms\Yii2Adapter\Element\Queries\Concerns\Category\QueriesCategoryGroups;
use CraftCms\Yii2Adapter\Element\Queries\Concerns\Category\QueriesRef;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Override;

use function CraftCms\Cms\currentUser;

/**
 * @template T of Category
 *
 * @extends ElementQuery<T>
 */
class CategoryQuery extends ElementQuery
{
    use QueriesRef;
    use QueriesCategoryGroups;

    #[Override]
    public bool $withStructure {
        get {
            if (!isset($this->withStructure)) {
                $this->withStructure = true;
            }

            return $this->withStructure;
        }
    }

    #[Override]
    protected string $table = DeprecatedTable::CATEGORIES;

    /**
     * @var bool|null Whether to only return categories that the user has permission to view.
     *
     * @used-by editable()
     */
    public ?bool $editable = null;

    public function __construct(array $config = [])
    {
        // Default status
        if (!isset($config['status'])) {
            $config['status'] = [
                Element::STATUS_ENABLED,
            ];
        }

        parent::__construct(Category::class, $config);

        $this->query->addSelect([
            'categories.groupId as groupId',
        ]);

        if (Cms::config()->staticStatuses) {
            $this->query->addSelect(['categories.status as status']);
        }

        $this->beforeQuery(function(self $query) {
            $this->applyAuthParam($query, $query->editable, 'viewCategories');
        });
    }

    /**
     * Sets the [[$editable]] property.
     *
     * @param  bool|null  $value  The property value (defaults to true)
     *
     * @uses $editable
     */
    public function editable(?bool $value = true): self
    {
        $this->editable = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the categories’ statuses.
     *
     * Possible values include:
     *
     * | Value | Fetches categories…
     * | - | -
     * | `'live'` _(default)_ | that are live.
     * | `'pending'` | that are pending (enabled with a Post Date in the future).
     * | `'expired'` | that are expired (enabled with an Expiry Date in the past).
     * | `'disabled'` | that are disabled.
     * | `['live', 'pending']` | that are live or pending.
     * | `['not', 'live', 'pending']` | that are not live or pending.
     *
     * ---
     *
     * ```twig
     * {# Fetch disabled categories #}
     * {% set {elements-var} = {twig-method}
     *   .status('disabled')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch disabled categories
     * ${elements-var} = {element-class}::find()
     *     ->status('disabled')
     *     ->all();
     * ```
     */
    #[Override]
    public function status(array|string|null $value): static
    {
        /** @var static */
        return parent::status($value);
    }

    /**
     * @throws QueryAbortedException
     */
    private function applyAuthParam(
        self $query,
        ?bool $value,
        string $permissionPrefix,
    ): void {
        if ($value === null) {
            return;
        }

        $user = currentUser();

        if (!$user) {
            throw new QueryAbortedException();
        }

        $groups = Craft::$app->getCategories()->getAllGroups();

        if (empty($groups)) {
            return;
        }

        $query->where(function(Builder $query) use ($value, $permissionPrefix, $user, $groups) {
            foreach ($groups as $group) {
                if ($user->can("$permissionPrefix:$group->uid")) {
                    $fullyAuthorizedGroupIds[] = $group->id;
                }
            }

            if (!empty($fullyAuthorizedGroupIds)) {
                if (count($fullyAuthorizedGroupIds) === count($groups)) {
                    // They have access to everything
                    if (!$value) {
                        throw new QueryAbortedException();
                    }

                    return;
                }

                $query->orWhereIn('categories.groupId', $fullyAuthorizedGroupIds);
            }

            // They don't have access to anything
            if ($value) {
                throw new QueryAbortedException();
            }
        }, boolean: $value ? 'and' : 'and not');
    }

    #[Override]
    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->groupId) {
            foreach (Arr::wrap($this->groupId) as $groupId) {
                $tags[] = "group:$groupId";
            }
        }

        return $tags;
    }

    #[Override]
    protected function fieldLayouts(): Collection
    {
        $this->normalizeGroupId($this);

        if ($this->groupId) {
            $fieldLayouts = [];

            foreach ($this->groupId as $groupId) {
                if ($group = Craft::$app->getCategories()->getGroupById($groupId)) {
                    $fieldLayouts[] = $group->getFieldLayout();
                }
            }

            return collect($fieldLayouts);
        }

        return collect();
    }
}
