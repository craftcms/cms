<?php
namespace Craft;

/**
 * Class PluginVersionUpdateStatus
 *
 * @package craft.app.enums
 */
abstract class PluginVersionUpdateStatus extends BaseEnum
{
	const UpToDate        = 'UpToDate';
	const UpdateAvailable = 'UpdateAvailable';
	const Deleted         = 'Deleted';
	const Active          = 'Active';
	const Unknown         = 'Unknown';
}
