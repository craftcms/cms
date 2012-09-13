<?php
namespace Blocks;

/**
 *
 */
abstract class BaseOptionsBlock extends BaseBlock
{
	protected $multi = false;

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'options' => array(AttributeType::Mixed, 'default' => array())
		);
	}

	/**
	 * Expand the options setting into an array.
	 *
	 * @access protected
	 * @param array $values
	 * @return array
	 */
	protected function preprocessSettings($values)
	{
		$options = array();

		if (!empty($values['options']))
		{
			$lines = array_filter(preg_split('/[\r\n]+/', $values['options']));

			foreach($lines as $line)
			{
				$parts = preg_split('/\s+=>\s+/', $line, 2);
				if ($parts[0])
					$options[$parts[0]] = (isset($parts[1])) ? $parts[1] : $parts[0];
			}
		}

		$values['options'] = $options;
		return $values;
	}

	/**
	 * Returns the content column type.
	 *
	 * @return string
	 */
	public function defineContentAttribute()
	{
		if ($this->multi)
			return AttributeType::Mixed;
		else
			return AttributeType::String;
	}

	/**
	 * Returns the block's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		// Prepare the options array for the textarea
		$options = '';
		foreach ($this->getSettings()->options as $value => $label)
		{
			if ((string)$value === (string)$label)
				$options .= $label."\n";
			else
				$options .= $value.' => '.$label."\n";
		}

		return TemplateHelper::render('_components/blocks/optionsblocksettings', array(
			'label'   => $this->getOptionsSettingsLabel(),
			'options' => $options
		));
	}

	/**
	 * Returns the label for the Options setting.
	 *
	 * @abstract
	 * @access protected
	 * @return string
	 */
	abstract protected function getOptionsSettingsLabel();
}
