<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m141109_000001_user_status_shuffle extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Adding locked column to users table...', LogLevel::Info, true);
		$this->addColumnAfter('users', 'locked', array(AttributeType::Bool, 'required' => true), 'status');

		Craft::log('Adding suspended column to users table...', LogLevel::Info, true);
		$this->addColumnAfter('users', 'suspended', array(AttributeType::Bool, 'required' => true), 'locked');

		Craft::log('Adding pending column to users table...', LogLevel::Info, true);
		$this->addColumnAfter('users', 'pending', array(AttributeType::Bool, 'required' => true), 'suspended');

		Craft::log('Adding archived column to users table...', LogLevel::Info, true);
		$this->addColumnAfter('users', 'archived', array(AttributeType::Bool, 'required' => true), 'pending');

		Craft::log('Updating locked users...', LogLevel::Info, true);
		$this->update('users', array(
			'locked' => 1
		), array(
			'status' => 'locked'
		));

		$this->update('users', array('locked' => 0), 'locked IS NULL');

		Craft::log('Updating pending users...', LogLevel::Info, true);
		$this->update('users', array(
			'pending' => 1
		), array(
			'status' => 'pending'
		));

		$this->update('users', array('pending' => 0), 'pending IS NULL');

		Craft::log('Updating archived users...', LogLevel::Info, true);
		$this->update('users', array(
			'archived' => 1
		), array(
			'status' => 'archived'
		));

		$this->update('users', array('archived' => 0), 'archived IS NULL');

		Craft::log('Updating suspended users...', LogLevel::Info, true);
		$this->update('users', array(
			'suspended' => 1
		), array(
			'status' => 'suspended'
		));

		$this->update('users', array('suspended' => 0), 'suspended IS NULL');

		Craft::log('Dropping status column from users table...', LogLevel::Info, true);
		$this->dropColumn('users', 'status');

		Craft::log('Done updating user statuses.', LogLevel::Info, true);

		return true;
	}
}
