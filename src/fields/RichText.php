<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\Field;
use craft\app\fields\data\RichTextData;
use craft\app\helpers\Db;
use craft\app\helpers\HtmlPurifier;
use craft\app\helpers\Io;
use craft\app\helpers\Json;
use craft\app\helpers\StringHelper;
use craft\app\models\Section;
use craft\app\validators\Handle;
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
    public $purifyHtml = false;

    /**
     * @var string The type of database column the field should have in the content table
     */
    public $columnType = Schema::TYPE_TEXT;

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

        $columns = [
            'text' => Craft::t('app', 'Text (stores about 64K)'),
            'mediumtext' => Craft::t('app', 'MediumText (stores about 4GB)')
        ];

        return Craft::$app->getView()->renderTemplate('_components/fieldtypes/RichText/settings',
            [
                'field' => $this,
                'configOptions' => $configOptions,
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
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
    {
        /** @var RichTextData|null $value */
        $configJs = $this->_getConfigJs();
        $this->_includeFieldResources($configJs);

        $id = Craft::$app->getView()->formatInputId($this->handle);
        $localeId = ($element ? $element->locale : Craft::$app->language);

        if (isset($this->model) && $this->model->translatable)
        {
            $locale = craft()->i18n->getLocaleData($localeId);
            $orientation = '"'.$locale->getOrientation().'"';
        }
        else
        {
            $orientation = 'Craft.orientation';
        }

        Craft::$app->getView()->registerJs('new Craft.RichTextInput('.
            '"'.Craft::$app->getView()->namespaceInputId($id).'", '.
            Json::encode($this->_getSectionSources()).', '.
            Json::encode($this->_getCategorySources()).', '.
            Json::encode($this->_getAssetSources()).', '.
            '"'.$localeId.'", ' .
            $orientation.', ' .
            $configJs.', '.
            '"'.static::$_redactorLang.'"'.
            ');');

        if ($value instanceof RichTextData) {
            $value = $value->getRawContent();
        }

        if (StringHelper::contains($value, '{')) {
            // Preserve the ref tags with hashes {type:id:url} => {type:id:url}#type:id
            $value = preg_replace_callback('/(href=|src=)([\'"])(\{(\w+\:\d+\:'.Handle::$handlePattern.')\})\2/',
                function ($matches) {
                    return $matches[1].$matches[2].$matches[3].'#'.$matches[4].$matches[2];
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
            // Give ourselves 10% wiggle room.
            $maxDbColumnSize = ceil($maxDbColumnSize * 0.9);

            if ($postContentSize > $maxDbColumnSize) {
                $errors[] = Craft::t('app', '{attribute} is too long.');
            }
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
        $value = preg_replace_callback('/(href=|src=)([\'"])[^\'"]+?#(\w+):(\d+)(:'.Handle::$handlePattern.')?\2/', function ($matches) {
                return $matches[1].$matches[2].'{'.$matches[3].':'.$matches[4].(!empty($matches[5]) ? $matches[5] : ':url').'}'.$matches[2];
            }, $value);

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
        } else {
            return true;
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the available section sources.
     *
     * @return array
     */
    private function _getSectionSources()
    {
        $sources = [];
        $sections = Craft::$app->getSections()->getAllSections();
        $showSingles = false;

        foreach ($sections as $section) {
            if ($section->type == Section::TYPE_SINGLE) {
                $showSingles = true;
            } else if ($section->hasUrls) {
                $sources[] = 'section:'.$section->id;
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
     * @return array
     */
    private function _getCategorySources()
    {
        $sources = [];
        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        foreach ($categoryGroups as $categoryGroup) {
            if ($categoryGroup->hasUrls) {
                $sources[] = 'group:'.$categoryGroup->id;
            }
        }

        return $sources;
    }

    /**
     * Returns the available volume sources.
     *
     * @return array
     */
    private function _getAssetSources()
    {
        $sources = [];
        $volumeIds = Craft::$app->getVolumes()->getAllVolumeIds();

        foreach ($volumeIds as $volumeId) {
            $sources[] = 'volume:'.$volumeId;
        }

        return $sources;
    }

    /**
     * Returns the Redactor config JS used by this field.
     *
     * @return string
     */
    private function _getConfigJs()
    {
        if ($this->configFile) {
            $configPath = Craft::$app->getPath()->getConfigPath().'/redactor/'.$this->configFile;
            $js = Io::getFileContents($configPath);
        }

        if (empty($js)) {
            $js = '{}';
        }

        return $js;
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
        Craft::$app->getView()->registerCssResource('lib/redactor/redactor.css');
        Craft::$app->getView()->registerCssResource('lib/redactor/plugins/pagebreak.css');

        // Gotta use the uncompressed Redactor JS until the compressed one gets our Live Preview menu fix
        Craft::$app->getView()->registerJsResource('lib/redactor/redactor.js');
        //Craft::$app->getView()->registerJsResource('lib/redactor/redactor'.(Craft::$app->getConfig()->get('useCompressedJs') ? '.min' : '').'.js');

        $this->_maybeIncludeRedactorPlugin($configJs, 'fullscreen', false);
        $this->_maybeIncludeRedactorPlugin($configJs, 'table', false);
        $this->_maybeIncludeRedactorPlugin($configJs, 'video', false);
        $this->_maybeIncludeRedactorPlugin($configJs, 'pagebreak', true);

        Craft::$app->getView()->includeTranslations(
            'Insert image',
            'Insert URL',
            'Choose image',
            'Link',
            'Link to an entry',
            'Insert link',
            'Unlink',
            'Link to an asset',
            'Link to a category');

        Craft::$app->getView()->registerJsResource('js/RichTextInput.js');

        // Check to see if the Redactor has been translated into the current locale
        if (Craft::$app->language != Craft::$app->sourceLanguage) {
            // First try to include the actual target locale
            if (!$this->_includeRedactorLangFile(Craft::$app->language)) {
                // Otherwise try to load the language (without the territory half)
                $languageId = Craft::$app->getLocale()->getLanguageID();
                $this->_includeRedactorLangFile($languageId);
            }
        }
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
        if (preg_match('/([\'"])'.$plugin.'\1/', $configJs)) {
            if ($includeCss) {
                Craft::$app->getView()->registerCssResource('lib/redactor/plugins/'.$plugin.'.css');
            }

            Craft::$app->getView()->registerJsResource('lib/redactor/plugins/'.$plugin.'.js');
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
        } else {
            return false;
        }
    }
}
