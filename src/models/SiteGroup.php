<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\records\SiteGroup as SiteGroupRecord;
use craft\validators\UniqueValidator;

/**
 * SiteGroup model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SiteGroup extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null UID
     */
    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Craft::t('app', 'Name'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['id'], 'number', 'integerOnly' => true];
        $rules[] = [['name'], 'string', 'max' => 255];
        $rules[] = [['name'], UniqueValidator::class, 'targetClass' => SiteGroupRecord::class];
        $rules[] = [['name'], 'required'];
        return $rules;
    }

    /**
     * Use the group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name ?: static::class;
    }

    /**
     * Returns the group's sites.
     *
     * @return Site[]
     */
    public function getSites(): array
    {
        return Craft::$app->getSites()->getSitesByGroupId($this->id);
    }

    /**
     * Returns the group’s site IDs.
     *
     * @return int[]
     */
    public function getSiteIds(): array
    {
        return ArrayHelper::getColumn($this->getSites(), 'id');
    }
}
