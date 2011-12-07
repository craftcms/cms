<?php

class BlocksTag extends Tag
{
	public function edition()
	{
		return Blocks::getEdition();
	}

	public function version()
	{
		return Blocks::getVersion();
	}

	public function build()
	{
		return Blocks::getBuildNumber();
	}

	public function fullVersion()
	{
		$edition = Blocks::getEdition();
		$version = Blocks::getVersion();
		$build = Blocks::getBuildNumber();

		return "Blocks {$edition} {$version}.{$build}";
	}

	public function config()
	{
		return new ConfigTag;
	}
}
