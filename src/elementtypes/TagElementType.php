<?php
namespace Craft;

/**
 * Tag element type
 */
class TagElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Tags');
	}

	/**
	 * Returns this element type's sources.
	 *
	 * @return array|false
	 */
	public function getSources()
	{
		$sources = array();

		foreach (craft()->tags->getAllTagSets() as $tagSet)
		{
			$key = 'tagset:'.$tagSet->id;

			$sources[$key] = array(
				'label'    => $tagSet->name,
				'criteria' => array('tagset' => $tagSet->id)
			);
		}

		return $sources;
	}

	/**
	 * Defines which model attributes should be searchable.
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		return array('name');
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			array('label' => Craft::t('Name'), 'attribute' => 'name'),
		);
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'name'  => AttributeType::String,
			'order' => array(AttributeType::String, 'default' => 'name desc'),
		);
	}

	/**
	 * Modifies an entries query targeting entries of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('tags.name')
			->join('tags tags', 'tags.id = elements.id');
	}

	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return TagModel::populateModel($row);
	}
}
