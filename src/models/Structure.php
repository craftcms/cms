<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;

/**
 * Class Structure model.
 *
 * @property bool $isSortable whether elements in this structure can be sorted by the current user
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Structure extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Max levels
     */
    public $maxLevels;

    /**
     * @var string|null UID
     */
    public $uid;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'maxLevels'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * Returns whether elements in this structure can be sorted by the current user.
     *
     * @return bool
     */
    public function getIsSortable(): bool
    {
        return Craft::$app->getSession()->checkAuthorization('editStructure:' . $this->id);
    }
}
