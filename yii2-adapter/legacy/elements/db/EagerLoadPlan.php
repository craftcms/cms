<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use CraftCms\Cms\Support\Arr;

/**
 * Class EagerLoadPlan
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Data\EagerLoadPlan} instead.
 */
class EagerLoadPlan extends \CraftCms\Cms\Element\Data\EagerLoadPlan
{
    public function __construct(
        array $config,
    ) {
        $handle = Arr::get($config, 'handle');
        $alias = Arr::get($config, 'alias');
        $criteria = Arr::get($config, 'criteria', []);
        $all = Arr::get($config, 'all', false);
        $count = Arr::get($config, 'count', false);
        $when = Arr::get($config, 'when');
        $nested = Arr::get($config, 'nested', []);
        $lazy = Arr::get($config, 'lazy', false);

        parent::__construct($handle, $alias, $criteria, $all, $count, $when, $nested, $lazy);
    }
}
