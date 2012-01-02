<?php

class ConfigTag extends Tag
{
	public function configPath()
	{
		return Blocks::app()->path->blocksConfigPath;
	}

	public function licenseKeys()
	{
		return Blocks::app()->site->licenseKeys;
	}
}
