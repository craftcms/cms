<?php
namespace Blocks;

/**
 *
 */
class LicenseKeyStatus
{
	const Valid = 'Valid';
	// we either can't find the given key, the key isn't tied to the version of Blocks they are running or it's not tied to the domain they are running on.
	const InvalidKey = 'InvalidKey';
	// can't find the license key in config/blocks.php
	const MissingKey = 'MissingKey';
}
