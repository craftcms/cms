<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Element;
use craft\base\GqlInterface;
use craft\base\GqlTrait;
use craft\base\Model;
use craft\gql\types\Structure as StructureType;
use craft\gql\types\StructureNode as StructureNodeType;

/**
 * Class Structure model.
 *
 * @property bool $isSortable whether elements in this structure can be sorted by the current user
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Structure extends Model implements GqlInterface
{
    // Traits
    // =========================================================================

    use GqlTrait;

    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
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

    public static function getGqlTypeList(): array
    {
       return [
            'Structure' => StructureType::class,
            'StructureNode' => StructureNodeType::class,
        ];
    }
}
