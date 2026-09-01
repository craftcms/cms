<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\Concerns\QueriesNestedElements;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Field\Elements\ContentBlock;

/**
 * @extends ElementQuery<ContentBlock>
 */
class ContentBlockQuery extends ElementQuery implements NestedElementQueryInterface
{
    use QueriesNestedElements;

    #[\Override]
    protected string $table = Table::CONTENTBLOCKS;

    /** @param array<string, mixed> $config */
    public function __construct(array $config = [])
    {
        parent::__construct(ContentBlock::class, $config);

        $this->query->addSelect([
            'contentblocks.fieldId as fieldId',
            'contentblocks.primaryOwnerId as primaryOwnerId',
        ]);
    }

    public function getFieldIdColumn(): string
    {
        return 'contentblocks.fieldId';
    }

    public function getPrimaryOwnerIdColumn(): string
    {
        return 'contentblocks.primaryOwnerId';
    }
}
