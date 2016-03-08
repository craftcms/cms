<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160304_000000_client_permissions extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Only do this if we're on Craft Client
		if (craft()->getEdition() == Craft::Client)
		{
			Craft::log('On Craft Client', LogLevel::Info, true);

			// Only do this if there is a client account setup.
			if ($client = craft()->users->getClient())
			{
				Craft::log('This install has an existing Client account.', LogLevel::Info, true);

				$allPermissions = craft()->userPermissions->getAllPermissions();
				$finalPermissions = array();

				// Recursively build a flattened list of permission handles.
				foreach ($allPermissions as $key => $value)
				{
					// First key is just the grouping, so ignore it.
					$this->_processPermission($value, $finalPermissions);
				}

				// Give the client account all available permissions.
				craft()->userPermissions->saveUserPermissions($client->id, $finalPermissions);

				Craft::log('Done assigning permissions to Client account.', LogLevel::Info, true);
			}
		}

		return true;
	}

	/**
	 * @param $value
	 * @param $finalPermissions
	 */
	private function _processPermission($value, &$finalPermissions)
	{
		foreach ($value as $permissionHandle => $value)
		{
			$finalPermissions[] = $permissionHandle;
			Craft::log('Client account has permission: '.$permissionHandle, LogLevel::Info, true);

			if (isset($value['nested']) && is_array($value['nested']))
			{
				$this->_processPermission($value['nested'], $finalPermissions);
			}
		}
	}
}
