<?php
namespace Blocks;

/**
 *
 */
class SectionRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sections';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'name'       => array(AttributeType::Name, 'required' => true),
			'handle'     => array(AttributeType::Handle, 'maxLength' => 45, 'required' => true),
			'titleLabel' => array(AttributeType::String, 'required' => true, 'default' => 'Title'),
			'hasUrls'    => array(AttributeType::Bool, 'default' => true),
			'urlFormat'  => array(AttributeType::String, 'label' => 'URL Format'),
			'template'   => AttributeType::Template,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'blocks'      => array(static::HAS_MANY, 'EntryBlockRecord', 'sectionId', 'order' => 'blocks.sortOrder'),
			'entries'     => array(static::HAS_MANY, 'EntryRecord', 'sectionId'),
			'totalBlocks' => array(static::STAT, 'EntryBlockRecord', 'sectionId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('handle'), 'unique' => true),
		);
	}

	/**
	 * @param array $attributes
	 * @param bool $clearErrors
	 * @return bool
	 */
	public function validate($attributes=null, $clearErrors=true)
	{
		parent::validate($attributes, $clearErrors);

		if ($this->hasUrls)
		{
			$validator = \CValidator::createValidator('required', $this, 'urlFormat', array());
			$validator->validate($this, $attributes);

			// Probably should move this into a validator class...
			if (strpos($this->urlFormat, '{slug}') === false)
			{
				$this->addError('urlFormat', Blocks::t('URL Format must contain “{slug}”'));
			}
		}

		return !$this->hasErrors();
	}
}
