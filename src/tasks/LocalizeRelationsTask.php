<?php
namespace Craft;

/**
 * The LocalizeRelations Task.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
		$this->_relations = craft()->db->createCommand()
			->select('id, sourceId, sourceLocale, targetId, sortOrder')
			->from('relations')
			->where('fieldId=:fieldId AND sourceLocale IS NULL', array('fieldId' => $this->getSettings()->fieldId))
			->queryAll();

		$this->_allLocales = craft()->i18n->getSiteLocaleIds();

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
			$affectedRows = craft()->db->createCommand()->update('relations', array('sourceLocale' => $this->_workingLocale), array('id' => $this->_relations[$step]['id']));

			for ($counter = 1; $counter < count($this->_allLocales); $counter++)
			{
				$this->_workingLocale = $this->_allLocales[$counter];

				$affectedRows = craft()->db->createCommand()->insert('relations', array(
					'fieldid'      => $this->getSettings()->fieldId,
					'sourceId'     => $this->_relations[$step]['sourceId'],
					'sourceLocale' => $this->_workingLocale,
					'targetId'     => $this->_relations[$step]['targetId'],
					'sortOrder'    => $this->_relations[$step]['sortOrder'],
				));
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
