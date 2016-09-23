<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\behaviors\FieldLayoutTrait;
use craft\app\helpers\Url;

/**
 * EntryType model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryType extends Model
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
     * @var integer Section ID
     */
    public $sectionId;

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
     * @var boolean Has title field
     */
    public $hasTitleField = true;

    /**
     * @var string Title label
     */
    public $titleLabel = 'Title';

    /**
     * @var string Title format
     */
    public $titleFormat;

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
                'elementType' => 'craft\app\elements\Entry'
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
                ['sectionId'],
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
                [
                    'id',
                    'sectionId',
                    'fieldLayoutId',
                    'name',
                    'handle',
                    'hasTitleField',
                    'titleLabel',
                    'titleFormat'
                ],
                'safe',
                'on' => 'search'
            ],
        ];
    }

    /**
     * Use the handle as the string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->handle;
    }

    /**
     * Returns the entry’s CP edit URL.
     *
     * @return string
     */
    public function getCpEditUrl()
    {
        return Url::getCpUrl('settings/sections/'.$this->sectionId.'/entrytypes/'.$this->id);
    }

    /**
     * Returns the entry type’s section.
     *
     * @return Section|null
     */
    public function getSection()
    {
        if ($this->sectionId) {
            return Craft::$app->getSections()->getSectionById($this->sectionId);
        }

        return null;
    }
}
