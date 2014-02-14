<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140212_000000_clean_up_structures extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Are we still on 1.3?
		if (craft()->db->columnExists('entries', 'root'))
		{
			$structureIds = craft()->db->createCommand()
				->select('id')
				->from('sections')
				->where('type = "structure"')
				->queryColumn();

			$elementsTable = 'entries';
			$structureIdColumn = 'sectionId';
			$levelColumn = 'depth';
		}
		else
		{
			$structureIds = craft()->db->createCommand()
				->select('id')
				->from('structures')
				->queryColumn();

			$elementsTable = 'structureelements';
			$structureIdColumn = 'structureId';
			$levelColumn = 'level';
		}

		foreach ($structureIds as $structureId)
		{
			// Get all of the elements for this structure
			$elements = craft()->db->createCommand()
				->select('id, lft, rgt, '.$levelColumn.' AS level')
				->from($elementsTable)
				->where($structureIdColumn.' = '.$structureId)
				->order('lft asc')
				->queryAll();

			if ($elements)
			{
				$originalElementData = array();
				$prevElement = null;
				$prevElementAtLevel = array();

				foreach ($elements as $elementIndex => $element)
				{
					// Make a copy of the original data so we can see if it changes
					$originalElementData[$elementIndex] = (object) $element;

					// Convert it to an object so changes stick
					$element = $elements[$elementIndex] = (object) $element;

					if ($elementIndex == 0)
					{
						$element->level = 0;
						$element->lft = 1;
					}
					else
					{
						// You never know...
						if ($element->level <= 0)
						{
							$element->level = 1;
						}

						// Make sure that we didn't skip a level, too
						else if ($element->level > $prevElement->level + 1)
						{
							$element->level = $prevElement->level + 1;
						}

						// Does this mark the end of the previous element?
						if ($element->level <= $prevElement->level)
						{
							$rgt = $prevElement->lft + 1;

							for ($level = $prevElement->level; $level >= $element->level; $level--)
							{
								$prevElementAtLevel[$level]->rgt = $rgt;
								unset($prevElementAtLevel[$level]);
								$rgt++;
							}

							$element->lft = $rgt;
						}
						else
						{
							$element->lft = $prevElement->lft + 1;
						}
					}

					$prevElementAtLevel[$element->level] = $element;
					$prevElement = $element;
				}

				// Close out the remaining elements
				$rgt = $prevElement->lft + 1;

				for ($level = $prevElement->level; $level >= 0; $level--)
				{
					$prevElementAtLevel[$level]->rgt = $rgt;
					unset($prevElementAtLevel[$level]);
					$rgt++;
				}

				// Now compare the original element data to the new data and save any rows that have changed
				foreach ($elements as $elementIndex => $element)
				{
					$originalData = $originalElementData[$elementIndex];

					$update = array();

					if ($element->lft != $originalData->lft) $update['lft'] = $element->lft;
					if ($element->rgt != $originalData->rgt) $update['rgt'] = $element->rgt;
					if ($element->level != $originalData->level) $update[$levelColumn] = $element->level;

					if ($update)
					{
						$this->update($elementsTable, $update, 'id = '.$element->id);
					}
				}
			}
		}

		return true;
	}
}
