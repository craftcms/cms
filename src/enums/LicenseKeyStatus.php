<?php
namespace Craft;

/**
 * Class LicenseKeyStatus
 *
 * @package craft.app.enums
 */
abstract class LicenseKeyStatus extends BaseEnum
{
	const Valid            = 'Valid';
	const Invalid          = 'Invalid';
	const Missing          = 'Missing';
	const Unverified       = 'Unverified';
	const MismatchedDomain = 'MismatchedDomain';
}
