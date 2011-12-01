<?php

class ConfigTag extends Tag
{
	public function blocksConfigPath()
	{
		return new StringTag(Blocks::app()->path->getBlocksConfigPath());
	}

	public function blocksEdition()
	{
		return new StringTag(Blocks::getEdition());
	}

	public function blocksVersion()
	{
		return new StringTag(Blocks::getVersion());
	}

	public function blocksBuild()
	{
		return new StringTag(Blocks::getBuildNumber());
	}

	public function licenseKeys()
	{
		return new ArrayTag(Blocks::app()->site->getLicenseKeys());
	}
}
