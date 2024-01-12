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
    public ?int $id = null;

    /**
     * @var int|null Section ID
     */
    public ?int $sectionId = null;

    /**
     * @var int|null Site ID
     */
    public ?int $siteId = null;

    /**
     * @var bool Enabled by default
     */
    public bool $enabledByDefault = true;

    /**
     * @var bool Has URLs?
     */
    public bool $hasUrls = false;

    /**
     * @var string|null URI format
     */
    public ?string $uriFormat = null;

    /**
     * @var string|null Entry template
     */
    public ?string $template = null;

    /**
     * @var Section|null
     */
    private ?Section $_section = null;

    /**
     * Returns the section.
     *
     * @return Section
     * @throws InvalidConfigException if [[sectionId]] is missing or invalid
     */
    public function getSection(): Section
    {
        if (isset($this->_section)) {
            return $this->_section;
        }

        if (!$this->sectionId) {
            throw new InvalidConfigException('Section site settings model is missing its section ID');
        }

        if (($this->_section = Craft::$app->getEntries()->getSectionById($this->sectionId)) === null) {
            throw new InvalidConfigException('Invalid section ID: ' . $this->sectionId);
        }

        return $this->_section;
    }

    /**
     * Sets the section.
     *
     * @param Section $section
     */
    public function setSection(Section $section): void
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
    public function attributeLabels(): array
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
        $rules[] = [['uriFormat', 'template'], 'trim'];
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
