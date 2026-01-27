<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\Concerns\QueriesNestedElements;
use CraftCms\Cms\Field\Elements\ContentBlock;

/**
 * @extends ElementQuery<ContentBlock>
 */
final class ContentBlockQuery extends ElementQuery
{
    use QueriesNestedElements;

    public function __construct(array $config = [])
    {
        parent::__construct(ContentBlock::class, $config);

        $this->joinElementTable(Table::CONTENTBLOCKS);

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
