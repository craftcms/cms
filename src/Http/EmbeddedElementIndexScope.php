<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\ElementIndexSourceSettings;
use CraftCms\Cms\Element\EmbeddedElementIndex;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Http\Contracts\EmbeddedIndexScope;
use CraftCms\Cms\Support\Typecast;

/**
 * Scopes an embedded element index to the criteria of an {@see EmbeddedElementIndex}.
 *
 * @since 6.0.0
 */
class EmbeddedElementIndexScope implements EmbeddedIndexScope
{
    private ?ElementIndexSourceSettings $indexSource = null;

    public function __construct(private readonly EmbeddedElementIndex $index) {}

    public static function fromToken(string $token): self
    {
        $index = EmbeddedElementIndex::fromToken($token);

        abort_if($index === null, 400, 'Invalid embedded index token');

        return new self($index);
    }

    public function indexSource(string $elementType, ?array $config = null): ElementIndexSourceSettings
    {
        abort_unless($elementType === $this->index->elementType(), 400, 'Invalid element type');

        return $this->indexSource ??= $this->index->sourceSettings();
    }

    public function canReorder(): bool
    {
        return false;
    }

    /**
     * Limits the query to the element-site rows matching the definition criteria, through a subquery,
     * so the status menu, filters and the resets done for element actions and inline editing can't replace them.
     * Rows are matched per site, since an element's status can differ between sites.
     */
    public function restrictQuery(ElementQueryInterface $query): void
    {
        $elementType = $this->index->elementType();
        $definitionQuery = $elementType::find()
            ->siteId('*')
            ->status(null)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->trashed(null);

        Typecast::configure($definitionQuery, ElementHelper::cleanseQueryCriteria($this->index->getCriteria()));

        $query->whereIn('elements_sites.id', $definitionQuery->toBase()->select('elements_sites.id')->reorder());
    }
}
