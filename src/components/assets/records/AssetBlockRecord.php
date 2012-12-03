<?php
namespace Blocks;

/**
 *
 */
class AssetBlockRecord extends BaseBlockRecord
{
	protected $reservedHandleWords = array('filename', 'kind', 'width', 'height', 'size', 'dateModified');

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetblocks';
	}
}
