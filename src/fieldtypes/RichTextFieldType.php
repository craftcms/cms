<?php
namespace Craft;

/**
 * Class RichTextFieldType
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
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
			'text'       => Craft::t('Text (stores about 64K)'),
			'mediumtext' => Craft::t('MediumText (stores about 4GB)')
		);

		return craft()->templates->render('_components/fieldtypes/RichText/settings', array(
			'settings' => $this->getSettings(),
			'configOptions' => $configOptions,
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
		$configJs = $this->_getConfigJs();
		$this->_includeFieldResources($configJs);

		$id = craft()->templates->formatInputId($name);

		craft()->templates->includeJs('new Craft.RichTextInput(' .
			'"'.craft()->templates->namespaceInputId($id).'", ' .
			JsonHelper::encode($this->_getSectionSources()).', ' .
			'"'.(isset($this->element) ? $this->element->locale : craft()->language).'", ' .
			$configJs.', ' .
			'"'.static::$_redactorLang.'"' .
		');');

		if ($value instanceof RichTextData)
		{
			$value = $value->getRawContent();
		}

		if (strpos($value, '{') !== false)
		{
			// Preserve the ref tags with hashes {type:id:url} => {type:id:url}#type:id
			$value = preg_replace_callback('/(href=|src=)([\'"])(\{(\w+\:\d+\:'.HandleValidator::$handlePattern.')\})\2/', function($matches)
			{
				return $matches[1].$matches[2].$matches[3].'#'.$matches[4].$matches[2];
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
		$value = preg_replace_callback('/(href=|src=)([\'"])[^\'"]+?#(\w+):(\d+)(:'.HandleValidator::$handlePattern.')?\2/', function($matches)
		{
			return $matches[1].$matches[2].'{'.$matches[3].':'.$matches[4].(!empty($matches[5]) ? $matches[5] : ':url').'}'.$matches[2];
		}, $value);

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
			// Give ourselves 10% wiggle room.
			$maxDbColumnSize = ceil($maxDbColumnSize * 0.9);

			if ($postContentSize > $maxDbColumnSize)
			{
				return Craft::t('{attribute} is too long.', array('attribute' => Craft::t($this->model->name)));
			}
		}

		return true;
	}

	/**
	 * @inheritDoc BaseFieldType::getStaticHtml()
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
			'configFile'  => AttributeType::String,
			'cleanupHtml' => array(AttributeType::Bool, 'default' => true),
			'purifyHtml'  => array(AttributeType::Bool, 'default' => false),
			'columnType'  => array(AttributeType::String),
		);
	}

	// Private Methods
	// =========================================================================

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
	 * Returns the Redactor config JS used by this field.
	 *
	 * @return string
	 */
	private function _getConfigJs()
	{
		if ($this->getSettings()->configFile)
		{
			$configPath = craft()->path->getConfigPath().'redactor/'.$this->getSettings()->configFile;
			$js = IOHelper::getFileContents($configPath);
		}

		if (empty($js))
		{
			$js = '{}';
		}

		return $js;
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
		craft()->templates->includeCssResource('lib/redactor/redactor.css');

		// Gotta use the uncompressed Redactor JS until the compressed one gets our Live Preview menu fix
		craft()->templates->includeJsResource('lib/redactor/redactor.js');
		//craft()->templates->includeJsResource('lib/redactor/redactor'.(craft()->config->get('useCompressedJs') ? '.min' : '').'.js');

		$this->_maybeIncludeRedactorPlugin($configJs, 'fullscreen', false);
		$this->_maybeIncludeRedactorPlugin($configJs, 'table', false);
		$this->_maybeIncludeRedactorPlugin($configJs, 'video', false);
		$this->_maybeIncludeRedactorPlugin($configJs, 'pagebreak', true);

		craft()->templates->includeTranslations('Insert image', 'Insert URL', 'Choose image', 'Link', 'Link to an entry', 'Insert link', 'Unlink', 'Link to an asset');

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
		if (preg_match('/([\'"])'.$plugin.'\1/', $configJs))
		{
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
