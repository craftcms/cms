<?php
namespace Craft;
use Craft;
use craft\app\db\Query;

/**
 * The LocalizeRelations Task.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.tasks
 * @since     2.3
 */
class LocalizeRelationsTask extends BaseTask
{
	// Properties
	// =========================================================================

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
	 * @inheritDoc ITask::getDescription()
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Localizing relations');
	}

	/**
	 * @inheritDoc ITask::getTotalSteps()
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$this->_relations = (new Query())
			->select('id, sourceId, sourceLocale, targetId, sortOrder')
			->from('{{%relations}}')
			->where(['fieldId' => $this->getSettings()->fieldId, 'sourceLocale' => null])
			->all();

		$this->_allLocales = Craft::$app->getI18n()->getSiteLocaleIds();

		return count($this->_relations);
	}

	/**
	 * @inheritDoc ITask::runStep()
	 *
	 * @param int $step
	 *
	 * @return bool
	 */
	public function runStep($step)
	{
		try
		{
			$this->_workingLocale = $this->_allLocales[0];

			// Update the existing one.
			$affectedRows = Craft::$app->getDb()->createCommand()->update(
				'{{$relations}}',
				['sourceLocale' => $this->_workingLocale],
				['id' => $this->_relations[$step]['id']]
			);

			for ($counter = 1; $counter < count($this->_allLocales); $counter++)
			{
				$this->_workingLocale = $this->_allLocales[$counter];

				$affectedRows = Craft::$app->getDb()->createCommand()->insert(
					'{{$relations}}',
					[
						'fieldid'      => $this->getSettings()->fieldId,
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
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'fieldId' => AttributeType::Number,
		);
	}
}
