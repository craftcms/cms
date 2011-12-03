<?php

class ConfigTag extends Tag
{
	public function blocksConfigPath()
	{
		return Blocks::app()->path->getBlocksConfigPath();
	}

	public function blocksEdition()
	{
		return Blocks::getEdition();
	}

	public function blocksVersion()
	{
		return Blocks::getVersion();
	}

	public function blocksBuild()
	{
		return Blocks::getBuildNumber();
	}

	public function licenseKeys()
	{
		return Blocks::app()->site->getLicenseKeys();
	}
}
