<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\tasks;

use Craft;
use craft\app\base\Task;
use craft\app\db\Query;

/**
 * LocalizeRelations represents a Localize Relations background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class LocalizeRelations extends Task
{
	// Properties
	// =========================================================================

	/**
	 * @var integer The field ID whose data should be localized
	 */
	public $fieldId;

	/**
	 * @var
	 */
	private $_relations;

	/**
	 * @var
	 */
	private $_allLocales;

	/**
	 * @var
	 */
	private $_workingLocale;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTotalSteps()
	{
		$this->_relations = (new Query())
			->select('id, sourceId, sourceLocale, targetId, sortOrder')
			->from('{{%relations}}')
			->where(['and', 'fieldId = :fieldId', 'sourceLocale is null'], [':fieldId' => $this->fieldId])
			->all();

		$this->_allLocales = Craft::$app->getI18n()->getSiteLocaleIds();

		return count($this->_relations);
	}

	/**
	 * @inheritdoc
	 */
	public function runStep($step)
	{
		$db = Craft::$app->getDb();
		try
		{
			$this->_workingLocale = $this->_allLocales[0];

			// Update the existing one.
			$db->createCommand()->update(
				'{{%relations}}',
				['sourceLocale' => $this->_workingLocale],
				['id' => $this->_relations[$step]['id']]
			)->execute();

			for ($counter = 1; $counter < count($this->_allLocales); $counter++)
			{
				$this->_workingLocale = $this->_allLocales[$counter];

				$db->createCommand()->insert(
					'{{%relations}}',
					[
						'fieldid'      => $this->fieldId,
						'sourceId'     => $this->_relations[$step]['sourceId'],
						'sourceLocale' => $this->_workingLocale,
						'targetId'     => $this->_relations[$step]['targetId'],
						'sortOrder'    => $this->_relations[$step]['sortOrder'],
					]
				);
			}

			return true;
		}
		catch (\Exception $e)
		{
			return 'An exception was thrown while trying to save relation for the field with Id '.$this->_relations[$step]['id'].' into the locale  “'.$this->_workingLocale.'”: '.$e->getMessage();
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function getDefaultDescription()
	{
		return Craft::t('app', 'Localizing relations');
	}
}
