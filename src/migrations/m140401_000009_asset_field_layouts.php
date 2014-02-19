<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_000009_asset_field_layouts extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Adding a fieldLayoutId column for Asset sources.', LogLevel::Info, true);

		if (!craft()->db->columnExists('assetsources', 'fieldLayoutId'))
		{
			$this->addColumnAfter('assetsources', 'fieldLayoutId', array('column' => ColumnType::Int), 'id');
			$this->addForeignKey('assetsources', 'fieldLayoutId', 'fieldlayouts', 'id', 'SET NULL');

			$layoutId = craft()->db->createCommand()
				->select('id')
				->from('fieldlayouts')
				->where('type = :elementType', array(':elementType' => ElementType::Asset))
				->queryScalar();

			if (!$layoutId)
			{
				Craft::log('No layouts found for Assets.', LogLevel::Info, true);
				return true;
			}
			else
			{
				$sourceIds = craft()->assetSources->getAllSourceIds();
				if (empty($sourceIds))
				{
					Craft::log("No sources exist for Assets.", LogLevel::Info, true);
					return true;
				}

				Craft::log('Copying the existing Assets layout to all sources.', LogLevel::Info, true);
				// Add the existing layout to the first source in list
				$sourceId = array_pop($sourceIds);
				craft()->db->createCommand()
					->update('assetsources', array('fieldLayoutId' => $layoutId), array('id' => $sourceId));

				$layout = FieldLayoutModel::populateModel(FieldLayoutRecord::model()->findByPk($layoutId));
				$fields = $layout->getFields();
				foreach ($sourceIds as $sourceId)
				{
					$layout = new FieldLayoutRecord();
					$layout->type = ElementType::Asset;
					$layout->save();

					foreach ($fields as $field)
					{
						$fieldRecord = new FieldLayoutFieldRecord();
						$fieldRecord->layoutId = $layout->id;
						$fieldRecord->fieldId = $field->fieldId;
						$fieldRecord->required = $field->required;
						$fieldRecord->sortOrder = $field->sortOrder;
						$fieldRecord->save();
					}

					craft()->db->createCommand()
						->update('assetsources', array('fieldLayoutId' => $layout->id), array('id' => $sourceId));
				}

				Craft::log('Copied the existing Assets layout to '.(count($sourceIds) + 1).' sources.', LogLevel::Info, true);
			}
		}

		return true;
	}
}
