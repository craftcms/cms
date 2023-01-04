<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\models\Section;
use craft\web\assets\quickpost\QuickPostAsset;

/**
 * QuickPost represents a Quick Post dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class QuickPost extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Quick Post');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft::getAlias('@appicons/newspaper.svg');
    }

    /**
     * @var string The site ID that the widget should pull entries from
     */
    public string $siteId = '';

    /**
     * @var int|null The ID of the section that the widget should post to
     */
    public ?int $section = null;

    /**
     * @var int|null The ID of the entry type that the widget should create
     */
    public ?int $entryType = null;

    /**
     * @var int[]|null The IDs of the fields that the widget should show
     */
    public ?array $fields = null;

    /**
     * @var Section|false|null
     */
    private Section|false|null $_section = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
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

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['section'], 'required'];
        $rules[] = [['section', 'entryType'], 'integer'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        // Find the sections the user has permission to create entries in
        $sections = [];

        foreach (Craft::$app->getSections()->getAllSections() as $section) {
            if ($section->type !== Section::TYPE_SINGLE) {
                if (Craft::$app->getUser()->checkPermission('createEntries:' . $section->uid)) {
                    $sections[] = $section;
                }
            }
        }

        $fieldsByEntryTypeId = [];
        foreach ($sections as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $fieldsByEntryTypeId[$entryType->id] = $entryType->getFieldLayout()->getCustomFieldElements();
            }
        }

        return Craft::$app->getView()->renderTemplate('_components/widgets/QuickPost/settings.twig',
            [
                'sections' => $sections,
                'fieldsByEntryTypeId' => $fieldsByEntryTypeId,
                'widget' => $this,
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
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
    public function getBodyHtml(): ?string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(QuickPostAsset::class);

        $section = $this->_getSection();

        if ($section === null) {
            return '<p>' . Craft::t('app', 'No section has been selected yet.') . '</p>';
        }

        $entryTypes = ArrayHelper::index($section->getEntryTypes(), 'id');

        if (empty($entryTypes)) {
            return '<p>' . Craft::t('app', 'No entry types exist for this section.') . '</p>';
        }

        if ($this->entryType && isset($entryTypes[$this->entryType])) {
            $entryTypeId = $this->entryType;
        } else {
            $entryTypeId = ArrayHelper::firstKey($entryTypes);
        }

        $entryType = $entryTypes[$entryTypeId];

        $params = [
            'siteId' => $this->siteId ?? Craft::$app->getSites()->getPrimarySite()->id,
            'sectionId' => $section->id,
            'typeId' => $entryTypeId,
        ];

        $view->startJsBuffer();

        $html = $view->renderTemplate('_components/widgets/QuickPost/body.twig',
            [
                'section' => $section,
                'entryType' => $entryType,
                'widget' => $this,
            ]);

        $fieldJs = $view->clearJsBuffer(false);
        $jsParams = Json::encode($params);
        $jsHtml = Json::encode($html);
        $js = <<<JS
new Craft.QuickPostWidget($this->id, $jsParams, () => {
  $fieldJs
}, $jsHtml);
JS;
        $view->registerJs($js);

        return $html;
    }

    /**
     * Returns the widget's section.
     *
     * @return Section|null
     */
    private function _getSection(): ?Section
    {
        if (!isset($this->_section)) {
            if ($this->section) {
                $this->_section = Craft::$app->getSections()->getSectionById($this->section);
            }

            if (!isset($this->_section)) {
                $this->_section = false;
            }
        }

        return $this->_section ?: null;
    }
}
