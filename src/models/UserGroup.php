<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\records\User as UserRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

Craft::$app->requireEdition(Craft::Pro);

/**
 * UserGroup model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserGroup extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var integer ID
     */
    public $id;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Handle
     */
    public $handle;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'number', 'integerOnly' => true],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['handle'], HandleValidator::class, 'reservedWords' => 'id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => UserRecord::class],
        ];
    }

    /**
     * Use the translated group name as the string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return Craft::t('site', $this->name);
    }

    /**
     * Returns whether the group has permission to perform a given action.
     *
     * @param string $permission
     *
     * @return boolean
     */
    public function can($permission)
    {
        if ($this->id) {
            return Craft::$app->getUserPermissions()->doesGroupHavePermission($this->id, $permission);
        }

        return false;
    }
}
