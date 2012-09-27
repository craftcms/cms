<?php
namespace Blocks;

/**
 *
 */
class LicenseKeyStatus
{
	const Valid = 'Valid';
	// We either can't find the given key, or it's not tied to the domain they are running on.
	const InvalidKey = 'InvalidKey';
	// Can't find the a license key at all.
	const MissingKey = 'MissingKey';
	//  Haven't been able to verify the license key status yet.
	const Unverified = 'Unverified';
}
