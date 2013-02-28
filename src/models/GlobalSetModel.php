<?php
namespace Craft;

/**
 * GlobalSet model class
 *
 * Used for transporting page data throughout the system.
 */
class GlobalSetModel extends BaseElementModel
{
	protected $elementType = ElementType::GlobalSet;

	private $_fieldLayout;

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'name'          => AttributeType::Name,
			'handle'        => AttributeType::Handle,
			'fieldLayoutId' => AttributeType::Number,
		));
	}

	/**
	 * Returns the global set's field layout.
	 *
	 * @return FieldLayoutModel
	 */
	public function getFieldLayout()
	{
		if (!isset($this->_fieldLayout))
		{
			if ($this->fieldLayoutId)
			{
				$this->_fieldLayout = craft()->fields->getLayoutById($this->fieldLayoutId);
			}

			if (empty($this->_fieldLayout))
			{
				$this->_fieldLayout = new FieldLayoutModel();
			}
		}

		return $this->_fieldLayout;
	}

	/**
	 * Sets the global set's field layout.
	 *
	 * @param FieldLayoutModel $fieldLayout
	 */
	public function setFieldLayout(FieldLayoutModel $fieldLayout)
	{
		$this->_fieldLayout = $fieldLayout;
	}
}
