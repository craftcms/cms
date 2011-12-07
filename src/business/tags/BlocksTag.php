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
		$name = 'Blocks'.($edition != 'Standard' ? ' '.$edition : '');
		$version = Blocks::getVersion();
		$build = Blocks::getBuildNumber();

		return "{$name} {$version}.{$build}";
	}

	public function config()
	{
		return new ConfigTag;
	}
}
