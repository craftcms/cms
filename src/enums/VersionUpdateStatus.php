<?php
namespace Craft;

/**
 * Class VersionUpdateStatus
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class VersionUpdateStatus extends BaseEnum
{
	const UpToDate        = 'UpToDate';
	const UpdateAvailable = 'UpdateAvailable';
}
