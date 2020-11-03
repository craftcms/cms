<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\Model;
use craft\validators\SingleSectionUriValidator;
use craft\validators\SiteIdValidator;
use craft\validators\UriFormatValidator;
use yii\base\InvalidConfigException;

/**
 * Section_SiteSettings model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Section_SiteSettings extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Section ID
     */
    public $sectionId;

    /**
     * @var int|null Site ID
     */
    public $siteId;

    /**
     * @var bool Enabled by default
     */
    public $enabledByDefault = true;

    /**
     * @var bool|null Has URLs?
     */
    public $hasUrls;

    /**
     * @var string|null URI format
     */
    public $uriFormat;

    /**
     * @var string|null Entry template
     */
    public $template;

    /**
     * @var Section|null
     */
    private $_section;

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function init()
    {
        // Typecast DB values
        $this->id = (int)$this->id ?: null;
        $this->sectionId = (int)$this->sectionId ?: null;
        $this->siteId = (int)$this->siteId ?: null;
        $this->enabledByDefault = (bool)$this->enabledByDefault;
        $this->hasUrls = (bool)$this->hasUrls;

        parent::init();
    }

    /**
     * Returns the section.
     *
     * @return Section
     * @throws InvalidConfigException if [[sectionId]] is missing or invalid
     */
    public function getSection(): Section
    {
        if ($this->_section !== null) {
            return $this->_section;
        }

        if (!$this->sectionId) {
            throw new InvalidConfigException('Section site settings model is missing its section ID');
        }

        if (($this->_section = Craft::$app->getSections()->getSectionById($this->sectionId)) === null) {
            throw new InvalidConfigException('Invalid section ID: ' . $this->sectionId);
        }

        return $this->_section;
    }

    /**
     * Sets the section.
     *
     * @param Section $section
     */
    public function setSection(Section $section)
    {
        $this->_section = $section;
    }

    /**
     * Returns the site.
     *
     * @return Site
     * @throws InvalidConfigException if [[siteId]] is missing or invalid
     */
    public function getSite(): Site
    {
        if (!$this->siteId) {
            throw new InvalidConfigException('Section site settings model is missing its site ID');
        }

        if (($site = Craft::$app->getSites()->getSiteById($this->siteId)) === null) {
            throw new InvalidConfigException('Invalid site ID: ' . $this->siteId);
        }

        return $site;
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
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'sectionId', 'siteId'], 'number', 'integerOnly' => true];
        $rules[] = [['siteId'], SiteIdValidator::class];
        $rules[] = [['template'], 'string', 'max' => 500];

        if ($this->getSection()->type == Section::TYPE_SINGLE) {
            $rules[] = ['uriFormat', SingleSectionUriValidator::class];
        } else {
            $rules[] = ['uriFormat', UriFormatValidator::class];
        }

        if ($this->hasUrls) {
            $rules[] = [['uriFormat'], 'required'];
        }

        return $rules;
    }
}
