<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130910_000005_fix_tags extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Get all of the Tags fields
		$tagFields = craft()->db->createCommand()
			->select('id, settings')
			->from('fields')
			->where(array('type' => 'Tags'))
			->queryAll();

		foreach ($tagFields as $field)
		{
			$settings = JsonHelper::decode($field['settings']);

			if (!empty($settings['source']) && strncmp($settings['source'], 'tagset:', 7) == 0)
			{
				$tagSetId = (int) mb_substr($settings['source'], 7);

				// Does that tag set still exist?
				$count = craft()->db->createCommand()
					->from('tagsets')
					->where(array('id' => $tagSetId))
					->count('id');

				if ($count)
				{
					// Now make sure all of the tags connected to this field actually belong to that set.
					// Otherwise we should duplicate the tag into the correct set

					$tags = craft()->db->createCommand()
						->select('r.id relationId, t.name')
						->from('relations r')
						->join('tags t', 't.id = r.childId')
						->where(array('and', 'r.fieldId = :fieldId', 't.setId != :setId'),
							array(':fieldId' => $field['id'], ':setId' => $tagSetId))
						->queryAll();

					foreach ($tags as $tag)
					{
						// Is there already a tag in the correct tag set with that name?
						$newTagId = craft()->db->createCommand()
							->select('id')
							->from('tags')
							->where(array('setId' => $tagSetId, 'name' => $tag['name']))
							->queryScalar();

						if (!$newTagId)
						{
							// Create a new row in elements
							craft()->db->createCommand()->insert('elements', array(
								'type'     => ElementType::Tag,
								'enabled'  => 1,
								'archived' => 0
							));

							// Get the new element ID
							$newTagId = craft()->db->getLastInsertID();

							$this->insert('tags', array(
								'id'    => $newTagId,
								'setId' => $tagSetId,
								'name'  => $tag['name']
							));
						}

						// Update the relation
						$this->update('relations', array(
							'childId' => $newTagId
						), array(
							'id' => $tag['relationId']
						));
					}
				}
				else
				{
					// Just delete any relations with this field
					$this->delete('relations', array('fieldId' => $field['id']));
				}
			}
		}

		return true;
	}
}
