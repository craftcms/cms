<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\cache;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use yii\caching\TagDependency;

/**
 * ElementQueryTagDependency is used to determine if an entry query’s cache tags have changed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.14
 */
class ElementQueryTagDependency extends TagDependency
{
    /**
     * @var ElementQueryInterface|null
     */
    public ?ElementQueryInterface $elementQuery = null;

    /**
     * Constructor
     *
     * @param ElementQueryInterface $elementQuery
     * @param array $config
     */
    public function __construct(ElementQueryInterface $elementQuery, array $config = [])
    {
        $this->elementQuery = $elementQuery;
        parent::__construct($config);
    }

    public function __sleep(): array
    {
        return ['tags', 'data', 'reusable'];
    }

    /**
     * @inheritdoc
     */
    protected function generateDependencyData($cache)
    {
        if ($this->elementQuery) {
            $this->tags = $this->elementQuery->getCacheTags();
        }
        return parent::generateDependencyData($cache);
    }
}
