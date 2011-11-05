<?php

interface IConfigService
{
	public function getDatabaseType();
	public function getDatabasePort();
	public function getDatabaseVersion();
	public function getDatabaseTablePrefix();
	public function getDatabaseSupportedTypes();
	public function getDatabaseServerName();
	public function getDatabaseName();
	public function getDatabaseAuthName();
	public function getDatabaseAuthPassword();
	public function getDatabaseCharset();
	public function getDatabaseCollation();
	public function getDatabaseRequiredVersionByType($databaseType);
	public function getLocalPHPVersion();
	public function getRequiredPHPVersion();
	public function getSiteLicenseKey();
	public function getSiteName();
	public function getSiteLanguage();
	public function getSiteUrl();
	public function updateConfigFile($filePath, $key, $value);
}
