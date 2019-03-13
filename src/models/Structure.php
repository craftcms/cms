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
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

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

    /**
     * @inheritdoc
     */
    public static function getGqlTypeDefinition(): array
    {
        if (self::$gqlTypes === null) {
            echo '';
            self::$gqlTypes = [
                self::getGqlTypeName() => new ObjectType([
                        'name' => self::getGqlTypeName(),
                        'fields' => self::getGqlTypeProperties()
                    ]
                ),
                self::getGqlTypeName() . 'Node' => new ObjectType([
                    'name' => self::getGqlTypeName() . 'Node',
                    'fields' => [
                        'id' => Type::id(),
                        'element' => Element::getFirstGqlTypeDefinition(),
                        'root' => Type::int(),
                        'lft' => Type::nonNull(Type::int()),
                        'rgt' => Type::nonNull(Type::int()),
                        'level' => Type::nonNull(Type::int()),
                    ]
                ])
            ];
        }

        return self::$gqlTypes;
    }
}
