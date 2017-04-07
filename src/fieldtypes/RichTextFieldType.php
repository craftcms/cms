<?php
namespace Craft;

/**
 * Class RichTextFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.fieldtypes
 * @since     1.0
 */
class RichTextFieldType extends BaseFieldType
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	private static $_redactorLang = 'en';

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Rich Text');
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$configOptions = array('' => Craft::t('Default'));
		$configPath = craft()->path->getConfigPath().'redactor/';

		if (IOHelper::folderExists($configPath))
		{
			$configFiles = IOHelper::getFolderContents($configPath, false, '\.json$');

			if (is_array($configFiles))
			{
				foreach ($configFiles as $file)
				{
					$configOptions[IOHelper::getFileName($file)] = IOHelper::getFileName($file, false);
				}
			}
		}

		$columns = array(
			'text'       => 'text (~64K)',
			'mediumtext' => 'mediumtext (~16MB)'
		);

		$sourceOptions = array();
		foreach (craft()->assetSources->getPublicSources() as $source)
		{
			$sourceOptions[] = array('label' => $source->name, 'value' => $source->id);
		}

		$transformOptions = array();
		foreach (craft()->assetTransforms->getAllTransforms() as $transform)
		{
			$transformOptions[] = array('label' => $transform->name, 'value' => $transform->id );
		}

		return craft()->templates->render('_components/fieldtypes/RichText/settings', array(
			'settings' => $this->getSettings(),
			'configOptions' => $configOptions,
			'assetSourceOptions' => $sourceOptions,
			'transformOptions' => $transformOptions,
			'columns' => $columns,
			'existing' => !empty($this->model->id),
		));
	}

	/**
	 * @inheritDoc IFieldType::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		$settings = $this->getSettings();

		// It hasn't always been a settings, so default to Text if it's not set.
		if (!$settings->getAttribute('columnType'))
		{
			return array(AttributeType::String, 'column' => ColumnType::Text);
		}

		return array(AttributeType::String, 'column' => $settings->columnType);
	}

	/**
	 * @inheritDoc IFieldType::prepValue()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValue($value)
	{
		if ($value)
		{
			// Prevent everyone from having to use the |raw filter when outputting RTE content
			$charset = craft()->templates->getTwig()->getCharset();
			return new RichTextData($value, $charset);
		}
		else
		{
			return null;
		}
	}

	/**
	 * @inheritDoc IFieldType::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		$configJs = $this->_getConfigJson();
		$this->_includeFieldResources($configJs);

		$id = craft()->templates->formatInputId($name);
		$localeId = (isset($this->element) ? $this->element->locale : craft()->language);

		$settings = array(
			'id'              => craft()->templates->namespaceInputId($id),
			'linkOptions'     => $this->_getLinkOptions(),
			'assetSources'    => $this->_getAssetSources(),
			'transforms'      => $this->_getTransforms(),
			'elementLocale'   => $localeId,
			'redactorConfig'  => JsonHelper::decode(JsonHelper::removeComments($configJs)),
			'redactorLang'    => static::$_redactorLang,
		);

		if (isset($this->model) && $this->model->translatable)
		{
			// Explicitly set the text direction
			$locale = craft()->i18n->getLocaleData($localeId);
			$settings['direction'] = $locale->getOrientation();
		}

		craft()->templates->includeJs('new Craft.RichTextInput('.JsonHelper::encode($settings).');');

		if ($value instanceof RichTextData)
		{
			$value = $value->getRawContent();
		}

		if (strpos($value, '{') !== false)
		{
			// Preserve the ref tags with hashes {type:id:url} => {type:id:url}#type:id
			$value = preg_replace_callback('/(href=|src=)([\'"])(\{(\w+\:\d+\:'.HandleValidator::$handlePattern.')\})(#[^\'"#]+)?\2/', function($matches)
			{
				return $matches[1].$matches[2].$matches[3].(!empty($matches[5]) ? $matches[5] : '').'#'.$matches[4].$matches[2];
			}, $value);

			// Now parse 'em
			$value = craft()->elements->parseRefs($value);
		}

		// Swap any <!--pagebreak-->'s with <hr>'s
		$value = str_replace('<!--pagebreak-->', '<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false" />', $value);

		return '<textarea id="'.$id.'" name="'.$name.'" style="display: none">'.htmlentities($value, ENT_NOQUOTES, 'UTF-8').'</textarea>';
	}

	/**
	 * @inheritDoc IFieldType::prepValueFromPost()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		// Temporary fix (hopefully) for a Redactor bug where some HTML will get submitted when the field is blank,
		// if any text was typed into the field, and then deleted
		if ($value == '<p><br></p>')
		{
			$value = '';
		}

		if ($value)
		{
			// Swap any pagebreak <hr>'s with <!--pagebreak-->'s
			$value = preg_replace('/<hr class="redactor_pagebreak".*?>/', '<!--pagebreak-->', $value);

			if ($this->getSettings()->purifyHtml)
			{
				$purifier = new \CHtmlPurifier();
				$purifier->setOptions(array(
					'Attr.AllowedFrameTargets' => array('_blank'),
					'HTML.AllowedComments' => array('pagebreak'),
				));

				$value = $purifier->purify($value);
			}

			if ($this->getSettings()->cleanupHtml)
			{
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
		$value = preg_replace_callback('/(href=|src=)([\'"])[^\'"#]+?(#[^\'"#]+)?(?:#|%23)(\w+):(\d+)(:'.HandleValidator::$handlePattern.')?\2/', function($matches)
		{
			$refTag = '{'.$matches[4].':'.$matches[5].(!empty($matches[6]) ? $matches[6] : ':url').'}';
			$hash = (!empty($matches[3]) ? $matches[3] : '');

			if ($hash)
			{
				// Make sure that the hash isn't actually part of the parsed URL
				// (someone's Entry URL Format could be "#{slug}", etc.)
				$url = craft()->elements->parseRefs($refTag);

				if (mb_strpos($url, $hash) !== false)
				{
					$hash = '';
				}
			}


			return $matches[1].$matches[2].$refTag.$hash.$matches[2];
		}, $value);

		// Encode any 4-byte UTF-8 characters.
		$value = StringHelper::encodeMb4($value);

		return $value;
	}

	/**
	 * @inheritDoc BaseFieldType::validate()
	 *
	 * @param mixed $value
	 *
	 * @return true|string|array
	 */
	public function validate($value)
	{
		$settings = $this->getSettings();

		// This wasn't always a setting.
		$columnType = !$settings->getAttribute('columnType') ? ColumnType::Text : $settings->getAttribute('columnType');

		$postContentSize = strlen($value);
		$maxDbColumnSize = DbHelper::getTextualColumnStorageCapacity($columnType);

		// Give ourselves 10% wiggle room.
		$maxDbColumnSize = ceil($maxDbColumnSize * 0.9);

		if ($postContentSize > $maxDbColumnSize)
		{
			return Craft::t('{attribute} is too long.', array('attribute' => Craft::t($this->model->name)));
		}

		return true;
	}

	/**
	 * @inheritDoc IFieldType::getStaticHtml()
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function getStaticHtml($value)
	{
		return '<div class="text">'.($value ? $value : '&nbsp;').'</div>';
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'configFile'            => AttributeType::String,
			'cleanupHtml'           => array(AttributeType::Bool, 'default' => true),
			'purifyHtml'            => array(AttributeType::Bool, 'default' => true),
			'columnType'            => array(AttributeType::String),
			'availableAssetSources' => AttributeType::Mixed,
			'availableTransforms'   => AttributeType::Mixed,
		);
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
	 * @return array
	 */
	private function _getLinkOptions()
	{
		$linkOptions = array();

		$sectionSources = $this->_getSectionSources();
		$categorySources = $this->_getCategorySources();

		if ($sectionSources)
		{
			$linkOptions[] = array(
				'optionTitle' => Craft::t('Link to an entry'),
				'elementType' => 'Entry',
				'sources' => $sectionSources,
			);
		}

		if ($categorySources)
		{
			$linkOptions[] = array(
				'optionTitle' => Craft::t('Link to a category'),
				'elementType' => 'Category',
				'sources' => $categorySources,
			);
		}

		// Give plugins a chance to add their own
		$allPluginLinkOptions = craft()->plugins->call('addRichTextLinkOptions', array(), true);

		foreach ($allPluginLinkOptions as $pluginLinkOptions)
		{
			$linkOptions = array_merge($linkOptions, $pluginLinkOptions);
		}

		return $linkOptions;
	}

	/**
	 * Get available section sources.
	 *
	 * @return array
	 */
	private function _getSectionSources()
	{
		$sources = array();
		$sections = craft()->sections->getAllSections();
		$showSingles = false;

		foreach ($sections as $section)
		{
			if ($section->type == SectionType::Single)
			{
				$showSingles = true;
			}
			else if ($section->hasUrls)
			{
				$sources[] = 'section:'.$section->id;
			}
		}

		if ($showSingles)
		{
			array_unshift($sources, 'singles');
		}

		return $sources;
	}

	/**
	 * Get available category sources.
	 *
	 * @return array
	 */
	private function _getCategorySources()
	{
		$sources = array();
		$categoryGroups = craft()->categories->getAllGroups();

		foreach ($categoryGroups as $categoryGroup)
		{
			if ($categoryGroup->hasUrls)
			{
				$sources[] = 'group:'.$categoryGroup->id;
			}
		}

		return $sources;
	}

	/**
	 * Get available Asset sources.
	 *
	 * @return array
	 */
	private function _getAssetSources()
	{
		$sources = array();

		$assetSourceIds = $this->getSettings()->availableAssetSources;

		if ($assetSourceIds === '*' || !$assetSourceIds)
		{
			$assetSourceIds = craft()->assetSources->getPublicSourceIds();
		}

		$folders = craft()->assets->findFolders(array(
			'sourceId' => $assetSourceIds,
			'parentId' => ':empty:'
		));

		// Sort it by source order.
		$list = array();

		foreach ($folders as $folder)
		{
		    $list[$folder->sourceId] = $folder->id;
		}

		foreach ($assetSourceIds as $assetSourceId) {
		    $sources[] = 'folder:'.$list[$assetSourceId];
        }

		return $sources;
	}

	/**
	 * Get available Transforms.
	 *
	 * @return array
	 */
	private function _getTransforms()
	{
		$transforms = craft()->assetTransforms->getAllTransforms('id');
		$settings = $this->getSettings();

		$transformIds = array_flip(!empty($settings->availableTransforms) && is_array($settings->availableTransforms)? $settings->availableTransforms : array());
		if (!empty($transformIds))
		{
			$transforms = array_intersect_key($transforms, $transformIds);
		}

		$transformList = array();
		foreach ($transforms as $transform)
		{
			$transformList[] = (object) array('handle' => HtmlHelper::encode($transform->handle), 'name' => HtmlHelper::encode($transform->name));
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
		if ($this->getSettings()->configFile)
		{
			$configPath = craft()->path->getConfigPath().'redactor/'.$this->getSettings()->configFile;
			$json = IOHelper::getFileContents($configPath);
		}

		if (empty($json))
		{
			$json = '{}';
		}

		return $json;
	}

	/**
	 * Includes the input resources.
	 *
	 * @param string $configJs
	 *
	 * @return null
	 */
	private function _includeFieldResources($configJs)
	{
		craft()->templates->includeCssResource('lib/redactor/redactor.min.css');

		craft()->templates->includeJsResource('lib/redactor/redactor'.(craft()->config->get('useCompressedJs') ? '.min' : '').'.js');

		$this->_maybeIncludeRedactorPlugin($configJs, 'fullscreen', false);
		$this->_maybeIncludeRedactorPlugin($configJs, 'source|html', false);
		$this->_maybeIncludeRedactorPlugin($configJs, 'table', false);
		$this->_maybeIncludeRedactorPlugin($configJs, 'video', false);
		$this->_maybeIncludeRedactorPlugin($configJs, 'pagebreak', true);

		craft()->templates->includeTranslations('Insert image', 'Insert URL', 'Choose image', 'Link', 'Link to an entry', 'Insert link', 'Unlink', 'Link to an asset', 'Link to a category');

		craft()->templates->includeJsResource('js/RichTextInput.js');

		// Check to see if the Redactor has been translated into the current locale
		if (craft()->language != craft()->sourceLanguage)
		{
			// First try to include the actual target locale
			if (!$this->_includeRedactorLangFile(craft()->language))
			{
				// Otherwise try to load the language (without the territory half)
				$languageId = craft()->locale->getLanguageID(craft()->language);

				if (!$this->_includeRedactorLangFile($languageId))
				{
					// If it's Norwegian Bokmål/Nynorsk, add plain ol' Norwegian as a fallback
					if ($languageId === 'nb' || $languageId === 'nn')
					{
						$this->_includeRedactorLangFile('no');
					}
				}
			}
		}

		$customTranslations = array(
			'fullscreen' => Craft::t('Fullscreen'),
			'insert-page-break' => Craft::t('Insert Page Break'),
			'table' => Craft::t('Table'),
			'insert-table' => Craft::t('Insert table'),
			'insert-row-above' => Craft::t('Insert row above'),
			'insert-row-below' => Craft::t('Insert row below'),
			'insert-column-left' => Craft::t('Insert column left'),
			'insert-column-right' => Craft::t('Insert column right'),
			'add-head' => Craft::t('Add head'),
			'delete-head' => Craft::t('Delete head'),
			'delete-column' => Craft::t('Delete column'),
			'delete-row' => Craft::t('Delete row'),
			'delete-table' => Craft::t('Delete table'),
			'video' => Craft::t('Video'),
			'video-html-code' => Craft::t('Video Embed Code or Youtube/Vimeo Link'),
		);

		craft()->templates->includeJs(
			'$.extend($.Redactor.opts.langs["'.static::$_redactorLang.'"], ' .
			JsonHelper::encode($customTranslations) .
			');');
	}

	/**
	 * Includes a plugin’s JS file, if it appears to be requested by the config file.
	 *
	 * @param string $configJs
	 * @param string $plugin
	 * @param bool $includeCss
	 *
	 * @return null
	 */
	private function _maybeIncludeRedactorPlugin($configJs, $plugin, $includeCss)
	{
		if (preg_match('/([\'"])(?:'.$plugin.')\1/', $configJs))
		{
			if (($pipe = strpos($plugin, '|')) !== false)
			{
				$plugin = substr($plugin, 0, $pipe);
			}

			if ($includeCss)
			{
				craft()->templates->includeCssResource('lib/redactor/plugins/'.$plugin.'.css');
			}

			craft()->templates->includeJsResource('lib/redactor/plugins/'.$plugin.'.js');
		}
	}

	/**
	 * Attempts to include a Redactor language file.
	 *
	 * @param string $lang
	 *
	 * @return bool
	 */
	private function _includeRedactorLangFile($lang)
	{
		$path = 'lib/redactor/lang/'.$lang.'.js';

		if (IOHelper::fileExists(craft()->path->getResourcesPath().$path))
		{
			craft()->templates->includeJsResource($path);
			static::$_redactorLang = $lang;

			return true;
		}
		else
		{
			return false;
		}
	}
}
