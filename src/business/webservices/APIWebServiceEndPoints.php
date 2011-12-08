<?php

class APIWebServiceEndPoints
{
	const VersionCheck = 'http://api.blockscms.com/admin.php?c=core&a=versioncheck';
	const DownloadPackage = 'http://api.blockscms.com/admin.php?c=core&a=downloadpackage';
	const GetCoreReleaseFileMD5 = 'http://api.blockscms.com/admin.php?c=core&a=getcorereleasefilemd5';
	const GetReleaseNumbersToUpdate = 'http://api.blockscms.com/admin.php?c=core&a=getreleasenumberstoupdate';
	const ValidateKeysByCredentials = 'http://api.blockscms.com/admin.php?c=core&a=validateKeysByCredentials';
}
