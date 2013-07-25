<?php
namespace Craft;

/**
 * Tags fieldtype
 */
class TagsFieldType extends BaseElementFieldType
{
	private $_tagSetId;

	/**
	 * @access protected
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'Tag';

	/**
	 * @access protected
	 * @var bool $allowMultipleSources Whether the field settings should allow multiple sources to be selected.
	 */
	protected $allowMultipleSources = false;

	/**
	 * @access protected
	 * @var bool $allowLimit Whether to allow the Limit setting.
	 */
	protected $allowLimit = false;

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $elements
	 * @return string
	 */
	public function getInputHtml($name, $elements)
	{
		$id = rtrim(preg_replace('/[\[\]]+/', '-', $name), '-');

		if (!($elements instanceof RelationFieldData))
		{
			$elements = new RelationFieldData();
		}

		return craft()->templates->render('_components/fieldtypes/Tags/input', array(
			'elementType'    => new ElementTypeVariable($this->getElementType()),
			'id'             => $id,
			'name'           => $name,
			'elements'       => $elements->all,
			'source'         => $this->getSettings()->source,
			'elementId'      => (!empty($this->element->id) ? $this->element->id : null),
		));
	}

	/**
	 * Performs any additional actions after the element has been saved.
	 */
	public function onAfterElementSave()
	{
		$tagSetId = $this->_getTagSetId();

		if ($tagSetId === false)
		{
			return;
		}

		$rawValue = $this->element->getRawContent($this->model->handle);
		$tagIds = is_array($rawValue) ? array_filter($rawValue) : array();

		foreach ($tagIds as $i => $tagId)
		{
			if (strncmp($tagId, 'new:', 4) == 0)
			{
				$name = substr($tagId, 4);

				// Last-minute check
				$criteria = craft()->elements->getCriteria(Elementtype::Tag, array(
					'tagsetId' => $tagSetId,
					'search'   => 'name:'.$name
				));

				$ids = $criteria->ids();

				if ($ids)
				{
					$tagIds[$i] = $ids[0];
				}
				else
				{
					$tag = new TagModel();
					$tag->setId = $tagSetId;
					$tag->name = $name;

					if (craft()->tags->saveTag($tag))
					{
						$tagIds[$i] = $tag->id;
					}
				}
			}
		}

		craft()->relations->saveRelations($this->model->id, $this->element->id, $tagIds);
	}

	/**
	 * Returns the tag set ID this field is associated with.
	 *
	 * @access private
	 * @return int|false
	 */
	private function _getTagSetId()
	{
		if (!isset($this->_tagSetId))
		{
			$source = $this->getSettings()->source;

			if (strncmp($source, 'tagset:', 7) == 0)
			{
				$this->_tagSetId = (int) substr($source, 7);
			}
			else
			{
				$this->_tagSetId = false;
			}
		}

		return $this->_tagSetId;
	}
}
