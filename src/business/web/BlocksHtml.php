<?php

class BlocksHtml extends BlocksBaseHtml
{
	public static function resource($resourcePath, $pluginHandle = null)
	{
		$resourceString = BLOCKS_RESOURCEPROCESSOR_URL.'?resourcePath='.BlocksBaseHtml::encode($resourcePath);

		if($pluginHandle != null)
		{
			$resourceString .= '&pluginHandle='.BlocksBaseHtml::encode($pluginHandle);
		}

		return $resourceString;
	}
}
