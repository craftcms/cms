<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Element\Queries;

use Craft;
use craft\elements\Tag;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Arr;
use CraftCms\Yii2Adapter\Database\DeprecatedTable;
use CraftCms\Yii2Adapter\Element\Queries\Concerns\Tag\QueriesTagGroups;
use Illuminate\Support\Collection;
use Override;

/**
 * @template T of Tag
 *
 * @extends ElementQuery<T>
 */
class TagQuery extends ElementQuery
{
    use QueriesTagGroups;

    #[Override]
    protected string $table = DeprecatedTable::TAGS;

    #[Override]
    protected array $defaultOrderBy = ['elements_sites.title' => SORT_ASC];

    public function __construct(array $config = [])
    {
        parent::__construct(Tag::class, $config);

        $this->query->addSelect([
            'tags.groupId as groupId',
        ]);
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
                if ($group = Craft::$app->getTags()->getTagGroupById($groupId)) {
                    $fieldLayouts[] = $group->getFieldLayout();
                }
            }

            return collect($fieldLayouts);
        }

        return collect();
    }
}
