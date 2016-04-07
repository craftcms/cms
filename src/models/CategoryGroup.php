<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\behaviors\FieldLayoutTrait;

/**
 * CategoryGroup model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CategoryGroup extends Model
{
    // Traits
    // =========================================================================

    use FieldLayoutTrait;

    // Properties
    // =========================================================================

    /**
     * @var integer ID
     */
    public $id;

    /**
     * @var integer Structure ID
     */
    public $structureId;

    /**
     * @var integer Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var string Name
     */
    public $name;

    /**
     * @var string Handle
     */
    public $handle;

    /**
     * @var boolean Has URLs
     */
    public $hasUrls = true;

    /**
     * @var string Template
     */
    public $template;

    /**
     * @var integer Max levels
     */
    public $maxLevels;

    /**
     * @var
     */
    private $_locales;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => 'craft\app\behaviors\FieldLayoutBehavior',
                'elementType' => 'craft\app\elements\Category'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['id'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['structureId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['fieldLayoutId'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                ['maxLevels'],
                'number',
                'min' => -2147483648,
                'max' => 2147483647,
                'integerOnly' => true
            ],
            [
                [
                    'id',
                    'structureId',
                    'fieldLayoutId',
                    'name',
                    'handle',
                    'hasUrls',
                    'template',
                    'maxLevels'
                ],
                'safe',
                'on' => 'search'
            ],
        ];
    }

    /**
     * Use the translated category group's name as the string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return Craft::t('site', $this->name);
    }

    /**
     * Returns the category's locale models.
     *
     * @return array
     */
    public function getLocales()
    {
        if (!isset($this->_locales)) {
            if ($this->id) {
                $this->_locales = Craft::$app->getCategories()->getGroupLocales($this->id, 'locale');
            } else {
                $this->_locales = [];
            }
        }

        return $this->_locales;
    }

    /**
     * Sets the section's locale models.
     *
     * @param array $locales
     *
     * @return void
     */
    public function setLocales($locales)
    {
        $this->_locales = $locales;
    }
}
