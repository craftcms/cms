<?php
namespace Blocks;

/**
 * Stores the available Blocks update info.
 */
class BlocksUpdateModel extends BaseModel
{
	/**
	 * @return array|void
	 */
	public function defineAttributes()
	{
		return array(
			'localBuild'              => AttributeType::String,
			'localVersion'            => AttributeType::String,
			'latestVersion'           => AttributeType::String,
			'latestBuild'             => AttributeType::String,
			'latestDate'              => AttributeType::DateTime,
			'realLatestVersion'       => AttributeType::String,
			'realLatestBuild'         => AttributeType::String,
			'realLatestDate'          => AttributeType::DateTime,
			'criticalUpdateAvailable' => AttributeType::Bool,
			'manualUpdateRequired'    => AttributeType::Bool,
			'breakpointRelease'       => AttributeType::Bool,
			'versionUpdateStatus'     => AttributeType::String,
			'releases'                => AttributeType::Mixed,
			'manualDownloadEndpoint'  => AttributeType::String,
		);
	}
}
