<?php
namespace Craft;

/**
 *
 */
class LicenseKeyStatus
{
	// Valid Key
	const Valid = 'Valid';

	// We either can't find the given key, or it's not tied to the domain they are running on.
	const InvalidKey = 'InvalidKey';

	// Can't find the a license key at all.
	const MissingKey = 'MissingKey';

	//  Haven't been able to verify the license key status yet.
	const Unverified = 'Unverified';

	// The domain associated with this license key is not the one the request was made with.
	const MismatchedDomain = 'MismatchedDomain';

	// There is a package that is not tied to this license key.
	const InvalidPackage = 'InvalidPackage';
}
