<?php
namespace Blocks;

/**
 * Asset source base class
 */
abstract class BaseAssetSourceType extends BaseComponent
{
	/**
	 * The type of component this is.
	 *
	 * @access protected
	 * @var string
	 */
	protected $componentType = 'AssetSourceType';

	/**
	 * Starts an indexing session
	 * @param $sessionId
	 * @return array
	 */
	abstract public function startIndex($sessionId);

	/**
	 * Process an indexing session
	 * @param $sessionId
	 * @param $offset
	 * @return mixed
	 */
	abstract public function processIndex($sessionId, $offset);
}
