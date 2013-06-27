<?php
namespace Craft;

/**
 *
 */
class RichTextFieldType extends BaseFieldType
{
	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Rich Text');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'configFile'  => AttributeType::String,
			'cleanupHtml' => array(AttributeType::Bool, 'default' => true),
		);
	}

	/**
	 * Returns the field's settings HTML.
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

		return craft()->templates->render('_components/fieldtypes/RichText/settings', array(
			'settings' => $this->getSettings(),
			'configOptions' => $configOptions
		));
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return array(AttributeType::String, 'column' => ColumnType::Text);
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
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
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		craft()->templates->includeCssResource('lib/redactor/redactor.css');
		craft()->templates->includeCssResource('lib/redactor/plugins/pagebreak.css');

		// Gotta use the uncompressed Redactor JS until the compressed one gets our Live Preview menu fix
		craft()->templates->includeJsResource('lib/redactor/redactor.js');
		//craft()->templates->includeJsResource('lib/redactor/redactor'.(craft()->config->get('useCompressedJs') ? '.min' : '').'.js');

		craft()->templates->includeJsResource('lib/redactor/plugins/fullscreen.js');
		craft()->templates->includeJsResource('lib/redactor/plugins/pagebreak.js');

		// Config?
		if ($this->getSettings()->configFile)
		{
			$configPath = craft()->path->getConfigPath().'redactor/'.$this->getSettings()->configFile;
			$config = IOHelper::getFileContents($configPath);
		}

		if (empty($config))
		{
			$config = '{}';
		}

		$sections = JsonHelper::encode($this->_getSectionSources());

		craft()->templates->includeJs(craft()->templates->render('_components/fieldtypes/RichText/init.js', array(
			'handle'   => $this->model->handle,
			'config'   => $config,
			'sections' => $sections
		)));

		// Swap any <!--pagebreak-->'s with <hr>'s
		$value = str_replace('<!--pagebreak-->', '<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false" />', $value);

		return '<textarea name="'.$name.'" class="redactor-'.$this->model->handle.'" style="display: none">'.htmlentities($value, ENT_NOQUOTES, 'UTF-8').'</textarea>';
	}

	/**
	 * Preps the post data before it's saved to the database.
	 *
	 * @access protected
	 * @param mixed $value
	 * @return mixed
	 */
	protected function prepPostData($value)
	{
		if ($value)
		{
			// Swap any pagebreak <hr>'s with <!--pagebreak-->'s
			$value = preg_replace('/<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false"\s*(\/)?>/', '<!--pagebreak-->', $value);

			if ($this->getSettings()->cleanupHtml)
			{
				// Remove <span>s
				$value = preg_replace('/<span[^>]*>/', '', $value);
				$value = str_replace('</span>', '', $value);

				// Remove inline styles
				$value = preg_replace('/(<(?:h1|h2|h3|h4|h5|h6|p|div|blockquote|pre)\b[^>]*)\s+style="[^"]*"/', '$1', $value);

				// Remove empty tags
				$value = preg_replace('/<(h1|h2|h3|h4|h5|h6|p|div|blockquote|pre)\s*><\/\1>/', '', $value);
			}
		}

		return $value;
	}

	/**
	 * Get available section sources.
	 *
	 * @return array
	 */
	private function _getSectionSources()
	{
		$sections = craft()->sections->getAllSections();
		$sources = array();
		foreach ($sections as $section)
		{
			if ($section->hasUrls)
			{
				$sources[] = 'section:' . $section->id;
			}
		}

		return $sources;
	}
}
