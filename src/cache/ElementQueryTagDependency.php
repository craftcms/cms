<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\cache;

use craft\elements\db\ElementQuery;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use Illuminate\Cache\Repository;

/**
 * ElementQueryTagDependency is used to determine if an entry query’s cache tags have changed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.14
 */
class ElementQueryTagDependency extends TagDependency
{
    /**
     * @var ElementQuery|null
     */
    public ?ElementQuery $elementQuery = null;

    public function __construct(ElementQuery $elementQuery, array|string $tags = [], public ?int $ttl = null)
    {
        $this->elementQuery = $elementQuery;
        parent::__construct($tags, $ttl);
    }

    public function __sleep(): array
    {
        return ['tags', 'data'];
    }

    protected function generateData(Repository $cache): array
    {
        if ($this->elementQuery) {
            $this->tags = array_unique(array_merge($this->tags, $this->elementQuery->getCacheTags()));
        }

        return parent::generateData($cache);
    }
}
