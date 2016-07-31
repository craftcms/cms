<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\widgets;

use Craft;
use craft\app\base\Widget;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\Json;
use craft\app\models\Section;

/**
 * QuickPost represents a Quick Post dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class QuickPost extends Widget
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Quick Post');
    }

    /**
     * @inheritdoc
     */
    public static function populateModel($model, $config)
    {
        // If we're saving the widget settings, all of the section-specific
        // attributes will be tucked away in a 'sections' array
        if (isset($config['sections'], $config['section'])) {
            $sectionId = $config['section'];

            if (isset($config['sections'][$sectionId])) {
                $config = array_merge($config, $config['sections'][$sectionId]);
            }

            unset($config['sections']);
        }

        parent::populateModel($model, $config);
    }

    // Properties
    // =========================================================================

    /**
     * @var integer The ID of the section that the widget should post to
     */
    public $section;

    /**
     * @var integer The ID of the entry type that the widget should create
     */
    public $entryType;

    /**
     * @var integer[] The IDs of the fields that the widget should show
     */
    public $fields;

    /**
     * @var
     */
    private $_section;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['section'], 'required'];
        $rules[] = [['section', 'entryType'], 'integer'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        // Find the sections the user has permission to create entries in
        $sections = [];

        foreach (Craft::$app->getSections()->getAllSections() as $section) {
            if ($section->type !== Section::TYPE_SINGLE) {
                if (Craft::$app->getUser()->checkPermission('createEntries:'.$section->id)) {
                    $sections[] = $section;
                }
            }
        }

        return Craft::$app->getView()->renderTemplate('_components/widgets/QuickPost/settings',
            [
                'sections' => $sections,
                'widget' => $this
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getIconPath()
    {
        return Craft::$app->getPath()->getResourcesPath().'/images/widgets/quick-post.svg';
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        $section = $this->_getSection();

        if ($section) {
            return Craft::t('app', 'Post a new {section} entry', ['section' => Craft::t('site', $section->name)]);
        }

        return static::displayName();
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        Craft::$app->getView()->registerTranslations('app', [
            'Entry saved.',
            'Couldn’t save entry.',
        ]);
        Craft::$app->getView()->registerJsResource('js/QuickPostWidget.js');

        $section = $this->_getSection();

        if ($section === null) {
            return '<p>'.Craft::t('app', 'No section has been selected yet.').'</p>';
        }

        $entryTypes = $section->getEntryTypes('id');

        if (!$entryTypes) {
            return '<p>'.Craft::t('app', 'No entry types exist for this section.').'</p>';
        }

        if ($this->entryType && isset($entryTypes[$this->entryType])) {
            $entryTypeId = $this->entryType;
        } else {
            $entryTypeId = ArrayHelper::getFirstKey($entryTypes);
        }

        $entryType = $entryTypes[$entryTypeId];

        $params = [
            'sectionId' => $section->id,
            'typeId' => $entryTypeId,
        ];

        Craft::$app->getView()->startJsBuffer();

        $html = Craft::$app->getView()->renderTemplate('_components/widgets/QuickPost/body',
            [
                'section' => $section,
                'entryType' => $entryType,
                'widget' => $this
            ]);

        $fieldJs = Craft::$app->getView()->clearJsBuffer(false);

        Craft::$app->getView()->registerJs('new Craft.QuickPostWidget('.
            $this->id.', '.
            Json::encode($params).', '.
            "function() {\n".$fieldJs.
            "\n});");

        return $html;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the widget's section.
     *
     * @return Section|null
     */
    private function _getSection()
    {
        if ($this->_section === null) {
            if ($this->section) {
                $this->_section = Craft::$app->getSections()->getSectionById($this->section);
            }

            if ($this->_section === null) {
                $this->_section = false;
            }
        }

        return $this->_section ?: null;
    }
}
