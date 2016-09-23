<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Element;
use craft\app\base\Field;
use craft\app\base\Volume;
use craft\app\fields\data\RichTextData;
use craft\app\helpers\Db;
use craft\app\helpers\Html;
use craft\app\helpers\HtmlPurifier;
use craft\app\helpers\Io;
use craft\app\helpers\Json;
use craft\app\helpers\StringHelper;
use craft\app\models\Section;
use craft\app\validators\HandleValidator;
use yii\base\Exception;
use yii\db\Schema;

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
    public static function displayName()
    {
        return Craft::t('app', 'Rich Text');
    }

    // Properties
    // =========================================================================

    /**
     * @var string The Redactor config file to use
     */
    public $configFile;

    /**
     * @var boolean Whether the HTML should be cleaned up on save
     */
    public $cleanupHtml = true;

    /**
     * @var boolean Whether the HTML should be purified on save
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
     * @var array The transforms available when selecting an image
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
        $configPath = Craft::$app->getPath()->getConfigPath().'/redactor';

        if (Io::folderExists($configPath)) {
            $configFiles = Io::getFolderContents($configPath, false, '\.json$');

            if (is_array($configFiles)) {
                foreach ($configFiles as $file) {
                    $configOptions[Io::getFilename($file)] = Io::getFilename($file, false);
                }
            }
        }

        $volumeOptions = [];
        /**
         * @var $volume Volume
         */
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
    public function getContentColumnType()
    {
        return $this->columnType;
    }

    /**
     * @inheritdoc
     */
    public function prepareValue($value, $element)
    {
        /** @var string|null $value */
        if ($value) {
            // Prevent everyone from having to use the |raw filter when outputting RTE content
            return new RichTextData($value);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
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
            'redactorLang' => static::$_redactorLang,
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

        if (StringHelper::contains($value, '{')) {
            // Preserve the ref tags with hashes {type:id:url} => {type:id:url}#type:id
            $value = preg_replace_callback('/(href=|src=)([\'"])(\{(\w+\:\d+\:'.HandleValidator::$handlePattern.')\})(#[^\'"#]+)?\2/',
                function ($matches) {
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
    public function validateValue($value, $element)
    {
        /** @var RichTextData|null $value */
        $errors = parent::validateValue($value, $element);

        $postContentSize = $value ? strlen($value->getRawContent()) : 0;
        $maxDbColumnSize = Db::getTextualColumnStorageCapacity($this->columnType);

        // Give ourselves 10% wiggle room.
        $maxDbColumnSize = ceil($maxDbColumnSize * 0.9);

        if ($postContentSize > $maxDbColumnSize) {
            $errors[] = Craft::t('app', '{attribute} is too long.');
        }

        return $errors;
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, $element)
    {
        /** @var RichTextData|null $value */
        return '<div class="text">'.($value ? $value : '&nbsp;').'</div>';
    }

    /**
     * @inheritdoc
     */
    public function prepareValueForDb($value, $element)
    {
        /** @var RichTextData|null $value */
        if (!$value) {
            return null;
        }

        // Get the raw value
        $value = $value->getRawContent();

        // Temporary fix (hopefully) for a Redactor bug where some HTML will get submitted when the field is blank,
        // if any text was typed into the field, and then deleted
        if ($value == '<p><br></p>') {
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
            function ($matches) {
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
    protected function isValueEmpty($value, $element)
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
    private function _getLinkOptions($element)
    {
        $linkOptions = [];

        $sectionSources = $this->_getSectionSources($element);
        $categorySources = $this->_getCategorySources($element);

        if ($sectionSources) {
            $linkOptions[] = [
                'optionTitle' => Craft::t('app', 'Link to an entry'),
                'elementType' => 'Entry',
                'sources' => $sectionSources,
            ];
        }

        if ($categorySources) {
            $linkOptions[] = [
                'optionTitle' => Craft::t('app', 'Link to a category'),
                'elementType' => 'Category',
                'sources' => $categorySources,
            ];
        }

        // Give plugins a chance to add their own
        $allPluginLinkOptions = Craft::$app->getPlugins()->call('addRichTextLinkOptions', [], true);

        foreach ($allPluginLinkOptions as $pluginLinkOptions) {
            $linkOptions = array_merge($linkOptions, $pluginLinkOptions);
        }

        return $linkOptions;
    }

    /**
     * Returns the available section sources.
     *
     * @param Element|null $element The element the field is associated with, if there is one
     *
     * @return array
     */
    private function _getSectionSources($element)
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
    private function _getCategorySources($element)
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
    private function _getVolumes()
    {
        $volumes = [];

        $volumeIds = $this->availableVolumes;

        if (!$volumeIds) {
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
    private function _getTransforms()
    {
        $transforms = Craft::$app->getAssetTransforms()->getAllTransforms('id');

        $transformIds = array_flip(!empty($this->availableTransforms) && is_array($this->availableTransforms) ? $this->availableTransforms : []);
        if (!empty($transformIds)) {
            $transforms = array_intersect_key($transforms, $transformIds);
        }

        $transformList = [];
        foreach ($transforms as $transform) {
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
    private function _getConfigJson()
    {
        if ($this->configFile) {
            $configPath = Craft::$app->getPath()->getConfigPath().'/redactor/'.$this->configFile;
            $json = Json::removeComments(Io::getFileContents($configPath));
        }

        if (empty($json)) {
            $json = '{}';
        }

        return $json;
    }

    /**
     * Includes the input resources.
     *
     * @param string $configJs
     *
     * @return void
     */
    private function _includeFieldResources($configJs)
    {
        $view = Craft::$app->getView();
        $view->registerCssResource('lib/redactor/redactor.css');
        $view->registerCssResource('lib/redactor/plugins/pagebreak.css');

        // Gotta use the uncompressed Redactor JS until the compressed one gets our Live Preview menu fix
        $view->registerJsResource('lib/redactor/redactor.js');
        //$view->registerJsResource('lib/redactor/redactor'.(Craft::$app->getConfig()->get('useCompressedJs') ? '.min' : '').'.js');

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

        $view->registerJsResource('js/RichTextInput.js');

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
            '$.extend($.Redactor.opts.langs["'.static::$_redactorLang.'"], '.
            Json::encode($customTranslations).
            ');');
    }

    /**
     * Includes a plugin’s JS file, if it appears to be requested by the config file.
     *
     * @param string  $configJs
     * @param string  $plugin
     * @param boolean $includeCss
     *
     * @return void
     */
    private function _maybeIncludeRedactorPlugin($configJs, $plugin, $includeCss)
    {
        if (preg_match('/([\'"])(?:'.$plugin.')\1/', $configJs)) {
            if (($pipe = strpos($plugin, '|')) !== false) {
                $plugin = substr($plugin, 0, $pipe);
            }

            $view = Craft::$app->getView();
            if ($includeCss) {
                $view->registerCssResource('lib/redactor/plugins/'.$plugin.'.css');
            }

            $view->registerJsResource('lib/redactor/plugins/'.$plugin.'.js');
        }
    }

    /**
     * Attempts to include a Redactor language file.
     *
     * @param string $lang
     *
     * @return boolean
     */
    private function _includeRedactorLangFile($lang)
    {
        $path = 'lib/redactor/lang/'.$lang.'.js';

        if (Io::fileExists(Craft::$app->getPath()->getResourcesPath().'/'.$path)) {
            Craft::$app->getView()->registerJsResource($path);
            static::$_redactorLang = $lang;

            return true;
        }

        return false;
    }
}
