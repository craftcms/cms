<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Tag;

/**
 * Class TagFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author  Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
abstract class TagFixture extends BaseElementFixture
{
    /**
     * @var array
     */
    protected $groupIds = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        foreach (Craft::$app->getTags()->getAllTagGroups() as $group) {
            $this->groupIds[$group->handle] = $group->id;
        }
    }

    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new Tag();
    }
}
