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
}
