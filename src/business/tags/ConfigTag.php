<?php

class ConfigTag extends Tag
{
	public function configPath()
	{
		return Blocks::app()->path->getBlocksConfigPath();
	}

	public function licenseKeys()
	{
		return Blocks::app()->site->getLicenseKeys();
	}
}
