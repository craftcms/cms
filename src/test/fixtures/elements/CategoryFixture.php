<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\elements\Category;

/**
 * Class CategoryFixture.
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
abstract class CategoryFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $modelClass = Category::class;

    /**
     * @var array
     */
    protected $groupIds = [];

    // Public methods
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        foreach (Craft::$app->getCategories()->getAllGroups() as $group) {
            $this->groupIds[$group->handle] = $group->id;
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['groupId', 'title']);
    }
}
