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
use craft\events\RegisterRedactorPluginEvent;
use craft\events\RegisterRichTextLinkOptionsEvent;
use craft\fields\data\RichTextData;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\HtmlPurifier;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\Section;
use craft\validators\HandleValidator;
use craft\web\assets\redactor\RedactorAsset;
use craft\web\assets\richtext\RichTextAsset;
use yii\base\Event;
use yii\db\Schema;

/**
 * RichText represents a Rich Text field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RichText extends Field
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterRedactorPluginEvent The event that is triggered when registering a Redactor plugin's resources.
     */
    const EVENT_REGISTER_REDACTOR_PLUGIN = 'registerRedactorPlugin';

    /**
     * @event RegisterRichTextLinkOptionsEvent The event that is triggered when registering the link options for the field.
     */
    const EVENT_REGISTER_LINK_OPTIONS = 'registerLinkOptions';

    // Static
    // =========================================================================

    /**
     * @var array List of the Redactor plugins that have already been registered for this request
     */
    private static $_registeredPlugins = [];

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Rich Text');
    }

    /**
     * Registers a Redactor plugin’s resources, if any
     *
     * @param string $plugin
     *
     * @return void
     */
    public static function registerRedactorPlugin(string $plugin)
    {
        if (isset(self::$_registeredPlugins[$plugin])) {
            return;
        }

        switch ($plugin) {
            case 'fullscreen': // no break
            case 'source': // no break
            case 'table': // no break
            case 'video': // no break
            case 'pagebreak':
                $am = Craft::$app->getAssetManager();
                $view = Craft::$app->getView();
                $view->registerJsFile($am->getPublishedUrl('@lib/redactor')."/plugins/{$plugin}.js", [
                    'depends' => RedactorAsset::class
                ]);
                if ($plugin === 'pagebreak') {
                    $view->registerCssFile($am->getPublishedUrl('@lib/redactor')."/plugins/{$plugin}.css");
                }
                break;
            default:
                // Maybe a plugin-supplied Redactor plugin
                Event::trigger(static::class, self::EVENT_REGISTER_REDACTOR_PLUGIN, new RegisterRedactorPluginEvent([
                    'plugin' => $plugin
                ]));
        }

        // Don't do this twice
        self::$_registeredPlugins[$plugin] = true;
    }

    // Properties
    // =========================================================================

    /**
     * @var string|null The Redactor config file to use
     */
    public $redactorConfig;

    /**
     * @var string|null The HTML Purifier config file to use
     */
    public $purifierConfig;

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
     * @var string|array|null The volumes that should be available for Image selection.
     */
    public $availableVolumes = '*';

    /**
     * @var string|array|null The transforms available when selecting an image
     */
    public $availableTransforms = '*';

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
        $volumeOptions = [];
        /** @var $volume Volume */
        foreach (Craft::$app->getVolumes()->getPublicVolumes() as $volume) {
            if ($volume->hasUrls) {
                $volumeOptions[] = [
                    'label' => Html::encode($volume->name),
                    'value' => $volume->id
                ];
            }
        }

        $transformOptions = [];
        foreach (Craft::$app->getAssetTransforms()->getAllTransforms() as $transform) {
            $transformOptions[] = [
                'label' => Html::encode($transform->name),
                'value' => $transform->id
            ];
        }

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/RichText/settings', [
            'field' => $this,
            'redactorConfigOptions' => $this->_getCustomConfigOptions('redactor'),
            'purifierConfigOptions' => $this->_getCustomConfigOptions('htmlpurifier'),
            'volumeOptions' => $volumeOptions,
            'transformOptions' => $transformOptions,
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
        if ($value === null || $value instanceof RichTextData) {
            return $value;
        }

        // Prevent everyone from having to use the |raw filter when outputting RTE content
        return new RichTextData($value);
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        /** @var RichTextData|null $value */
        /** @var Element $element */
        $redactorConfig = $this->_getRedactorConfig();
        $this->_registerFieldResources($redactorConfig);

        $view = Craft::$app->getView();
        $id = $view->formatInputId($this->handle);
        $site = ($element ? $element->getSite() : Craft::$app->getSites()->currentSite);

        $settings = [
            'id' => $view->namespaceInputId($id),
            'linkOptions' => $this->_getLinkOptions($element),
            'volumes' => $this->_getVolumeKeys(),
            'transforms' => $this->_getTransforms(),
            'elementSiteId' => $site->id,
            'redactorConfig' => $redactorConfig,
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

        if ($value !== null) {
            // Parse reference tags
            $value = $this->_parseRefs($value);

            // Swap any <!--pagebreak-->'s with <hr>'s
            $value = str_replace('<!--pagebreak-->', '<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false" />', $value);
        }

        return '<textarea id="'.$id.'" name="'.$this->handle.'" style="display: none">'.htmlentities($value, ENT_NOQUOTES, 'UTF-8').'</textarea>';
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
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        $keywords = parent::getSearchKeywords($value, $element);

        if (Craft::$app->getDb()->getIsMysql()) {
            $keywords = StringHelper::encodeMb4($keywords);
        }

        return $keywords;
    }

    /**
     * @inheritdoc
     */
    public function isEmpty($value): bool
    {
        if ($value === null) {
            return true;
        }

        /** @var RichTextData $value */
        return parent::isEmpty($value->getRawContent());
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
                // Parse reference tags so HTMLPurifier doesn't encode the curly braces
                $value = $this->_parseRefs($value);

                $value = HtmlPurifier::process($value, $this->_getPurifierConfig());
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
            '/(href=|src=)([\'"])[^\'"#]+?(#[^\'"#]+)?(?:#|%23)([\w\\\\]+)\:(\d+)(\:(?:transform\:)?'.HandleValidator::$handlePattern.')?\2/',
            function($matches) {
                // Create the ref tag, and make sure :url is in there
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

        if (Craft::$app->getDb()->getIsMysql()) {
            // Encode any 4-byte UTF-8 characters.
            $value = StringHelper::encodeMb4($value);
        }

        return $value;
    }

    // Private Methods
    // =========================================================================

    /**
     * Parse ref tags in URLs, while preserving the original tag values in the URL fragments
     * (e.g. `href="{entry:id:url}"` => `href="[entry-url]#entry:id:url"`)
     *
     * @param string $value
     *
     * @return string
     */
    private function _parseRefs(string $value = null): string
    {
        if (!StringHelper::contains($value, '{')) {
            return $value;
        }

        return preg_replace_callback('/(href=|src=)([\'"])(\{([\w\\\\]+\:\d+\:(?:transform\:)?'.HandleValidator::$handlePattern.')\})(#[^\'"#]+)?\2/', function($matches) {
            list (, $attr, $q, $refTag, $ref) = $matches;
            $fragment = $matches[5] ?? '';

            return $attr.$q.Craft::$app->getElements()->parseRefs($refTag).$fragment.'#'.$ref.$q;
        }, $value);
    }

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
                'refHandle' => Entry::refHandle(),
                'sources' => $sectionSources,
            ];
        }

        if (!empty($categorySources)) {
            $linkOptions[] = [
                'optionTitle' => Craft::t('app', 'Link to a category'),
                'elementType' => Category::class,
                'refHandle' => Category::refHandle(),
                'sources' => $categorySources,
            ];
        }

        // Give plugins a chance to add their own
        $event = new RegisterRichTextLinkOptionsEvent([
            'linkOptions' => $linkOptions
        ]);
        $this->trigger(self::EVENT_REGISTER_LINK_OPTIONS, $event);
        $linkOptions = $event->linkOptions;

        // Fill in any missing ref handles
        foreach ($linkOptions as &$linkOption) {
            if (!isset($linkOption['refHandle'])) {
                /** @var ElementInterface|string $class */
                $class = $linkOption['elementType'];
                $linkOption['refHandle'] = $class::refHandle() ?? $class;
            }
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
    private function _getSectionSources(Element $element = null): array
    {
        $sources = [];
        $sections = Craft::$app->getSections()->getAllSections();
        $showSingles = false;

        foreach ($sections as $section) {
            if ($section->type === Section::TYPE_SINGLE) {
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
     * @return string[]
     */
    private function _getVolumeKeys(): array
    {
        if (!$this->availableVolumes) {
            return [];
        }

        $criteria = ['parentId' => ':empty:'];

        if ($this->availableVolumes !== '*') {
            $criteria['volumeId'] = $this->availableVolumes;
        }

        $folders = Craft::$app->getAssets()->findFolders($criteria);

        // Sort volumes in the same order as they are sorted in the CP
        $sortedVolumeIds = Craft::$app->getVolumes()->getAllVolumeIds();
        $sortedVolumeIds = array_flip($sortedVolumeIds);

        $volumeKeys = [];

        usort($folders, function($a, $b) use ($sortedVolumeIds) {
            // In case Temporary volumes ever make an appearance in RTF modals, sort them to the end of the list.
            $aOrder = $sortedVolumeIds[$a->volumeId] ?? PHP_INT_MAX;
            $bOrder = $sortedVolumeIds[$b->volumeId] ?? PHP_INT_MAX;

            return $aOrder - $bOrder;
        });

        foreach ($folders as $folder) {
            $volumeKeys[] = 'folder:'.$folder->id;
        }

        return $volumeKeys;
    }

    /**
     * Get available transforms.
     *
     * @return array
     */
    private function _getTransforms(): array
    {
        if (!$this->availableTransforms) {
            return [];
        }

        $allTransforms = Craft::$app->getAssetTransforms()->getAllTransforms();
        $transformList = [];

        foreach ($allTransforms as $transform) {
            if (!is_array($this->availableTransforms) || in_array($transform->id, $this->availableTransforms, false)) {
                $transformList[] = [
                    'handle' => Html::encode($transform->handle),
                    'name' => Html::encode($transform->name)
                ];
            }
        }

        return $transformList;
    }

    /**
     * Returns the available Redactor config options.
     *
     * @param string $dir The directory name within the config/ folder to look for config files
     *
     * @return array
     */
    private function _getCustomConfigOptions(string $dir): array
    {
        $options = ['' => Craft::t('app', 'Default')];
        $path = Craft::$app->getPath()->getConfigPath().DIRECTORY_SEPARATOR.$dir;

        if (is_dir($path)) {
            $files = FileHelper::findFiles($path, [
                'only' => ['*.json'],
                'recursive' => false
            ]);

            foreach ($files as $file) {
                $options[pathinfo($file, PATHINFO_BASENAME)] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return $options;
    }

    /**
     * Returns a JSON-decoded config, if it exists.
     *
     * @param string      $dir  The directory name within the config/ folder to look for the config file
     * @param string|null $file The filename to load
     *
     * @return array|false The config, or false if the file doesn't exist
     */
    private function _getConfig(string $dir, string $file = null)
    {
        if (!$file) {
            return false;
        }

        $path = Craft::$app->getPath()->getConfigPath().DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$file;

        if (!is_file($path)) {
            return false;
        }

        return Json::decode(file_get_contents($path));
    }

    /**
     * Returns the Redactor config used by this field.
     *
     * @return array
     */
    private function _getRedactorConfig(): array
    {
        return $this->_getConfig('redactor', $this->redactorConfig) ?: [];
    }

    /**
     * Returns the HTML Purifier config used by this field.
     *
     * @return array
     */
    private function _getPurifierConfig(): array
    {
        if ($config = $this->_getConfig('htmlpurifier', $this->purifierConfig)) {
            return $config;
        }

        // Default config
        return [
            'Attr.AllowedFrameTargets' => ['_blank'],
            'HTML.AllowedComments' => ['pagebreak'],
        ];
    }

    /**
     * Registers the front end resources for the field.
     *
     * @param array $redactorConfig
     *
     * @return void
     */
    private function _registerFieldResources(array $redactorConfig)
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(RichTextAsset::class);

        if (isset($redactorConfig['plugins'])) {
            foreach ($redactorConfig['plugins'] as $plugin) {
                static::registerRedactorPlugin($plugin);
            }
        }

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
