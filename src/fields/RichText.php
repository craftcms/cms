<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\fields;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\Volume;
use craft\elements\Category;
use craft\elements\Entry;
use craft\events\RegisterRichTextLinkOptionsEvent;
use craft\fields\data\RichTextData;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\HtmlPurifier;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\Section;
use craft\validators\HandleValidator;
use craft\web\assets\redactor\RedactorAsset;
use craft\web\assets\richtext\RichTextAsset;
use yii\db\Schema;
use yii\validators\StringValidator;

/**
 * RichText represents a Rich Text field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RichText extends Field
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Rich Text');
    }

    // Constants
    // =========================================================================

    /**
     * @event RegisterRichTextLinkOptionsEvent The event that is triggered when registering the link options for the field.
     */
    const EVENT_REGISTER_LINK_OPTIONS = 'registerLinkOptions';

    // Properties
    // =========================================================================

    /**
     * @var string|null The Redactor config file to use
     */
    public $configFile;

    /**
     * @var bool Whether the HTML should be cleaned up on save
     */
    public $cleanupHtml = true;

    /**
     * @var bool Whether the HTML should be purified on save
     */
    public $purifyHtml = true;

    /**
     * @var string The type of database column the field should have in the content table
     */
    public $columnType = Schema::TYPE_TEXT;

    /**
     * @var array The volumes that should be available for Image selection
     */
    public $availableVolumes = [];

    /**
     * @var string|array The transforms available when selecting an image
     */
    public $availableTransforms = [];

    /**
     * @var string
     */
    private static $_redactorLang = 'en';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $configOptions = ['' => Craft::t('app', 'Default')];
        $configPath = Craft::$app->getPath()->getConfigPath().DIRECTORY_SEPARATOR.'redactor';

        if (is_dir($configPath)) {
            $configFiles = FileHelper::findFiles($configPath, [
                'only' => ['*.json'],
                'recursive' => false
            ]);

            foreach ($configFiles as $file) {
                $configOptions[pathinfo($file, PATHINFO_BASENAME)] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        $volumeOptions = [];
        /** @var $volume Volume */
        foreach (Craft::$app->getVolumes()->getPublicVolumes() as $volume) {
            if ($volume->hasUrls) {
                $volumeOptions[] = [
                    'label' => $volume->name,
                    'value' => $volume->id
                ];
            }
        }

        $transformOptions = [];
        foreach (Craft::$app->getAssetTransforms()->getAllTransforms() as $transform) {
            $transformOptions[] = [
                'label' => $transform->name,
                'value' => $transform->id
            ];
        }

        $columns = [
            'text' => Craft::t('app', 'Text (stores about 64K)'),
            'mediumtext' => Craft::t('app', 'MediumText (stores about 4GB)')
        ];

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/RichText/settings',
            [
                'field' => $this,
                'configOptions' => $configOptions,
                'volumeOptions' => $volumeOptions,
                'transformOptions' => $transformOptions,
                'columns' => $columns,
                'existing' => !empty($this->id),
            ]);
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return $this->columnType;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        /** @var string|null $value */
        if ($value !== null) {
            // Prevent everyone from having to use the |raw filter when outputting RTE content
            return new RichTextData($value);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var RichTextData|null $value */
        /** @var Element $element */
        $configJs = $this->_getConfigJson();
        $this->_includeFieldResources($configJs);

        $view = Craft::$app->getView();
        $id = $view->formatInputId($this->handle);
        $site = ($element ? $element->getSite() : Craft::$app->getSites()->currentSite);

        $settings = [
            'id' => $view->namespaceInputId($id),
            'linkOptions' => $this->_getLinkOptions($element),
            'volumes' => $this->_getVolumes(),
            'transforms' => $this->_getTransforms(),
            'elementSiteId' => $site->id,
            'redactorConfig' => Json::decode($configJs),
            'redactorLang' => self::$_redactorLang,
        ];

        if ($this->translationMethod != self::TRANSLATION_METHOD_NONE) {
            // Explicitly set the text direction
            $locale = Craft::$app->getI18n()->getLocaleById($site->language);
            $settings['direction'] = $locale->getOrientation();
        }

        $view->registerJs('new Craft.RichTextInput('.Json::encode($settings).');');

        if ($value instanceof RichTextData) {
            $value = $value->getRawContent();
        }

        if ($value !== null && StringHelper::contains($value, '{')) {
            // Preserve the ref tags with hashes {type:id:url} => {type:id:url}#type:id
            $value = preg_replace_callback('/(href=|src=)([\'"])(\{(\w+\:\d+\:'.HandleValidator::$handlePattern.')\})(#[^\'"#]+)?\2/',
                function($matches) {
                    return $matches[1].$matches[2].$matches[3].(!empty($matches[5]) ? $matches[5] : '').'#'.$matches[4].$matches[2];
                }, $value);

            // Now parse 'em
            $value = Craft::$app->getElements()->parseRefs($value);
        }

        // Swap any <!--pagebreak-->'s with <hr>'s
        $value = str_replace('<!--pagebreak-->', '<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false" />', $value);

        return '<textarea id="'.$id.'" name="'.$this->handle.'" style="display: none">'.htmlentities($value, ENT_NOQUOTES, 'UTF-8').'</textarea>';
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = 'validateLength';

        return $rules;
    }

    /**
     * Validates the field value.
     *
     * @param ElementInterface $element
     * @param array|null       $params
     *
     * @return void
     */
    public function validateLength(ElementInterface $element, array $params = null)
    {
        /** @var Element $element */
        /** @var RichTextData $value */
        $value = $element->getFieldValue($this->handle);

        // Set the max size based on the column's storage capacity (with a little wiggle room)
        $max = Db::getTextualColumnStorageCapacity($this->columnType);

        if ($max === null) {
            // null means unlimited, so no need to validate this
            return;
        }

        $validator = new StringValidator([
            'max' => ceil($max * 0.9),
        ]);

        if (!$validator->validate($value->getRawContent(), $error)) {
            $element->addError($this->handle, $error);
        }
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, ElementInterface $element): string
    {
        /** @var RichTextData|null $value */
        return '<div class="text">'.($value ?: '&nbsp;').'</div>';
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        /** @var RichTextData|null $value */
        if (!$value) {
            return null;
        }

        // Get the raw value
        $value = $value->getRawContent();

        // Temporary fix (hopefully) for a Redactor bug where some HTML will get submitted when the field is blank,
        // if any text was typed into the field, and then deleted
        if ($value === '<p><br></p>') {
            $value = '';
        }

        if ($value) {
            // Swap any pagebreak <hr>'s with <!--pagebreak-->'s
            $value = preg_replace('/<hr class="redactor_pagebreak".*?>/', '<!--pagebreak-->', $value);

            if ($this->purifyHtml) {
                $value = HtmlPurifier::process($value, [
                    'Attr.AllowedFrameTargets' => ['_blank'],
                    'HTML.AllowedComments' => ['pagebreak']
                ]);
            }

            if ($this->cleanupHtml) {
                // Remove <span> and <font> tags
                $value = preg_replace('/<(?:span|font)\b[^>]*>/', '', $value);
                $value = preg_replace('/<\/(?:span|font)>/', '', $value);

                // Remove inline styles
                $value = preg_replace('/(<(?:h1|h2|h3|h4|h5|h6|p|div|blockquote|pre|strong|em|b|i|u|a)\b[^>]*)\s+style="[^"]*"/', '$1', $value);

                // Remove empty tags
                $value = preg_replace('/<(h1|h2|h3|h4|h5|h6|p|div|blockquote|pre|strong|em|a|b|i|u)\s*><\/\1>/', '', $value);
            }
        }

        // Find any element URLs and swap them with ref tags
        $value = preg_replace_callback(
            '/(href=|src=)([\'"])[^\'"#]+?(#[^\'"#]+)?(?:#|%23)(\w+):(\d+)(:'.HandleValidator::$handlePattern.')?\2/',
            function($matches) {
                $refTag = '{'.$matches[4].':'.$matches[5].(!empty($matches[6]) ? $matches[6] : ':url').'}';
                $hash = (!empty($matches[3]) ? $matches[3] : '');

                if ($hash) {
                    // Make sure that the hash isn't actually part of the parsed URL
                    // (someone's Entry URL Format could be "#{slug}", etc.)
                    $url = Craft::$app->getElements()->parseRefs($refTag);

                    if (mb_strpos($url, $hash) !== false) {
                        $hash = '';
                    }
                }

                return $matches[1].$matches[2].$refTag.$hash.$matches[2];
            },
            $value);

        // Encode any 4-byte UTF-8 characters.
        $value = StringHelper::encodeMb4($value);

        return $value;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isValueEmpty($value, ElementInterface $element): bool
    {
        /** @var RichTextData|null $value */
        if ($value) {
            $rawContent = $value->getRawContent();

            return empty($rawContent);
        }

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the link options available to the field.
     *
     * Each link option is represented by an array with the following keys:
     *
     * - `optionTitle` (required) – the user-facing option title that appears in the Link dropdown menu
     * - `elementType` (required) – the element type class that the option should be linking to
     * - `sources` (optional) – the sources that the user should be able to select elements from
     * - `criteria` (optional) – any specific element criteria parameters that should limit which elements the user can select
     * - `storageKey` (optional) – the localStorage key that should be used to store the element selector modal state (defaults to RichTextFieldType.LinkTo[ElementType])
     *
     * @param Element|null $element The element the field is associated with, if there is one
     *
     * @return array
     */
    private function _getLinkOptions(Element $element = null): array
    {
        $linkOptions = [];

        $sectionSources = $this->_getSectionSources($element);
        $categorySources = $this->_getCategorySources($element);

        if (!empty($sectionSources)) {
            $linkOptions[] = [
                'optionTitle' => Craft::t('app', 'Link to an entry'),
                'elementType' => Entry::class,
                'sources' => $sectionSources,
            ];
        }

        if (!empty($categorySources)) {
            $linkOptions[] = [
                'optionTitle' => Craft::t('app', 'Link to a category'),
                'elementType' => Category::class,
                'sources' => $categorySources,
            ];
        }

        // Give plugins a chance to add their own
        $event = new RegisterRichTextLinkOptionsEvent([
            'linkOptions' => $linkOptions
        ]);
        $this->trigger(self::EVENT_REGISTER_LINK_OPTIONS, $event);

        return $event->linkOptions;
    }

    /**
     * Returns the available section sources.
     *
     * @param Element|null $element The element the field is associated with, if there is one
     *
     * @return array
     */
    private function _getSectionSources(Element $element = null): array
    {
        $sources = [];
        $sections = Craft::$app->getSections()->getAllSections();
        $showSingles = false;

        foreach ($sections as $section) {
            if ($section->type == Section::TYPE_SINGLE) {
                $showSingles = true;
            } else if ($element) {
                // Does the section have URLs in the same site as the element we're editing?
                $sectionSiteSettings = $section->getSiteSettings();
                if (isset($sectionSiteSettings[$element->siteId]) && $sectionSiteSettings[$element->siteId]->hasUrls) {
                    $sources[] = 'section:'.$section->id;
                }
            }
        }

        if ($showSingles) {
            array_unshift($sources, 'singles');
        }

        return $sources;
    }

    /**
     * Returns the available category sources.
     *
     * @param Element|null $element The element the field is associated with, if there is one
     *
     * @return array
     */
    private function _getCategorySources(Element $element = null): array
    {
        $sources = [];

        if ($element) {
            $categoryGroups = Craft::$app->getCategories()->getAllGroups();

            foreach ($categoryGroups as $categoryGroup) {
                // Does the category group have URLs in the same site as the element we're editing?
                $categoryGroupSiteSettings = $categoryGroup->getSiteSettings();
                if (isset($categoryGroupSiteSettings[$element->siteId]) && $categoryGroupSiteSettings[$element->siteId]->hasUrls) {
                    $sources[] = 'group:'.$categoryGroup->id;
                }
            }
        }

        return $sources;
    }

    /**
     * Returns the available volumes.
     *
     * @return array
     */
    private function _getVolumes(): array
    {
        $volumes = [];

        $volumeIds = $this->availableVolumes;

        if (empty($volumeIds)) {
            // TODO: change to getPublicVolumeIds() when it exists
            $volumeIds = Craft::$app->getVolumes()->getPublicVolumeIds();
        }

        $folders = Craft::$app->getAssets()->findFolders([
            'volumeId' => $volumeIds,
            'parentId' => ':empty:'
        ]);

        foreach ($folders as $folder) {
            $volumes[] = 'folder:'.$folder->id;
        }

        return $volumes;
    }

    /**
     * Get available transforms.
     *
     * @return array
     */
    private function _getTransforms(): array
    {
        $allTransforms = Craft::$app->getAssetTransforms()->getAllTransforms();
        $transformList = [];

        foreach ($allTransforms as $transform) {
            if (is_array($this->availableTransforms) && !in_array($transform->id, $this->availableTransforms, false)) {
                continue;
            }
            $transformList[] = (object)[
                'handle' => Html::encode($transform->handle),
                'name' => Html::encode($transform->name)
            ];
        }

        return $transformList;
    }

    /**
     * Returns the Redactor config JSON used by this field.
     *
     * @return string
     */
    private function _getConfigJson(): string
    {
        if (!$this->configFile) {
            return '{}';
        }

        $configPath = Craft::$app->getPath()->getConfigPath().DIRECTORY_SEPARATOR.'redactor'.DIRECTORY_SEPARATOR.$this->configFile;

        if (!is_file($configPath)) {
            Craft::warning("Redactor config file doesn't exist: {$configPath}", __METHOD__);

            return '{}';
        }

        return file_get_contents($configPath);
    }

    /**
     * Includes the input resources.
     *
     * @param string $configJs
     *
     * @return void
     */
    private function _includeFieldResources(string $configJs)
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(RichTextAsset::class);

        $this->_maybeIncludeRedactorPlugin($configJs, 'fullscreen', false);
        $this->_maybeIncludeRedactorPlugin($configJs, 'source|html', false);
        $this->_maybeIncludeRedactorPlugin($configJs, 'table', false);
        $this->_maybeIncludeRedactorPlugin($configJs, 'video', false);
        $this->_maybeIncludeRedactorPlugin($configJs, 'pagebreak', true);

        $view->registerTranslations('app', [
            'Insert image',
            'Insert URL',
            'Choose image',
            'Link',
            'Link to an entry',
            'Insert link',
            'Unlink',
            'Link to an asset',
            'Link to a category',
        ]);

        // Check to see if the Redactor has been translated into the current site
        if (Craft::$app->language != Craft::$app->sourceLanguage) {
            // First try to include the actual target language
            if (!$this->_includeRedactorLangFile(Craft::$app->language)) {
                // Otherwise try to load the language (without the territory half)
                $languageId = Craft::$app->getLocale()->getLanguageID();
                $this->_includeRedactorLangFile($languageId);
            }
        }

        $customTranslations = [
            'fullscreen' => Craft::t('app', 'Fullscreen'),
            'insert-page-break' => Craft::t('app', 'Insert Page Break'),
            'table' => Craft::t('app', 'Table'),
            'insert-table' => Craft::t('app', 'Insert table'),
            'insert-row-above' => Craft::t('app', 'Insert row above'),
            'insert-row-below' => Craft::t('app', 'Insert row below'),
            'insert-column-left' => Craft::t('app', 'Insert column left'),
            'insert-column-right' => Craft::t('app', 'Insert column right'),
            'add-head' => Craft::t('app', 'Add head'),
            'delete-head' => Craft::t('app', 'Delete head'),
            'delete-column' => Craft::t('app', 'Delete column'),
            'delete-row' => Craft::t('app', 'Delete row'),
            'delete-table' => Craft::t('app', 'Delete table'),
            'video' => Craft::t('app', 'Video'),
            'video-html-code' => Craft::t('app', 'Video Embed Code or Youtube/Vimeo Link'),
        ];

        $view->registerJs(
            '$.extend($.Redactor.opts.langs["'.self::$_redactorLang.'"], '.
            Json::encode($customTranslations).
            ');');
    }

    /**
     * Includes a plugin’s JS file, if it appears to be requested by the config file.
     *
     * @param string $configJs
     * @param string $plugin
     * @param bool   $includeCss
     *
     * @return void
     */
    private function _maybeIncludeRedactorPlugin(string $configJs, string $plugin, bool $includeCss)
    {
        if (preg_match('/([\'"])(?:'.$plugin.')\1/', $configJs)) {
            if (($pipe = strpos($plugin, '|')) !== false) {
                $plugin = substr($plugin, 0, $pipe);
            }

            $am = Craft::$app->getAssetManager();
            $view = Craft::$app->getView();

            if ($includeCss) {
                $view->registerCssFile($am->getPublishedUrl('@lib/redactor')."/plugins/{$plugin}.css");
            }

            $view->registerJsFile($am->getPublishedUrl('@lib/redactor')."/plugins/{$plugin}.js", [
                'depends' => RedactorAsset::class
            ]);
        }
    }

    /**
     * Attempts to include a Redactor language file.
     *
     * @param string $lang
     *
     * @return bool
     */
    private function _includeRedactorLangFile(string $lang): bool
    {
        $redactorPath = Craft::getAlias('@lib/redactor');
        $subPath = "/lang/{$lang}.js";
        $fullPath = $redactorPath.$subPath;

        if (!is_file($fullPath)) {
            return false;
        }

        $am = Craft::$app->getAssetManager();
        $view = Craft::$app->getView();
        $view->registerJsFile($am->getPublishedUrl($redactorPath).$subPath, [
            'depends' => RedactorAsset::class
        ]);
        self::$_redactorLang = $lang;

        return true;
    }
}
