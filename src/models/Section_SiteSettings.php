<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\validators\SingleSectionUriValidator;
use craft\app\validators\SiteIdValidator;
use craft\app\validators\UriFormatValidator;
use yii\base\InvalidConfigException;

/**
 * Section_SiteSettings model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Section_SiteSettings extends Model
{
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
     * @var integer Site ID
     */
    public $siteId;

    /**
     * @var boolean Enabled by default
     */
    public $enabledByDefault = true;

    /**
     * @var boolean Has URLs?
     */
    public $hasUrls;

    /**
     * @var string URI format
     */
    public $uriFormat;

    /**
     * @var string Entry template
     */
    public $template;

    /**
     * @var Section
     */
    private $_section;

    // Public Methods
    // =========================================================================

    /**
     * Returns the section.
     *
     * @return Section
     * @throws InvalidConfigException if the section is invalid
     */
    public function getSection()
    {
        if (!isset($this->_section) && $this->sectionId) {
            $this->_section = Craft::$app->getSections()->getSectionById($this->sectionId);
        }

        if (!$this->_section) {
            throw new InvalidConfigException('Invalid section');
        }

        return $this->_section;
    }

    /**
     * Sets the section.
     *
     * @param Section $section
     *
     * @return void
     */
    public function setSection(Section $section)
    {
        $this->_section = $section;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labels = [
            'template' => Craft::t('app', 'Template'),
        ];

        if ($this->getSection()->type == Section::TYPE_SINGLE) {
            $labels['uriFormat'] = Craft::t('app', 'URI');
        } else {
            $labels['uriFormat'] = Craft::t('app', 'Entry URI Format');
        }

        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['id', 'sectionId', 'siteId'], 'number', 'integerOnly' => true],
            [['siteId'], SiteIdValidator::class],
            [['template'], 'string', 'max' => 500],
        ];

        if ($this->getSection()->type == Section::TYPE_SINGLE) {
            $rules[] = ['uriFormat', SingleSectionUriValidator::class];
        } else {
            $rules[] = ['uriFormat', UriFormatValidator::class];
        }

        if ($this->hasUrls || $this->getSection()->type == Section::TYPE_SINGLE) {
            $rules[] = [['uriFormat'], 'required'];
        }

        return $rules;
    }
}
