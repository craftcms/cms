<?php
namespace Blocks;

/**
 *
 */
class AssetsHelper
{
	const ActionKeepBoth = 'keep_both';
	const ActionReplace = 'replace';
	const ActionCancel = 'cancel';

	const IndexSkipItemsPattern = '/.*(Thumbs\.db|__MACOSX|__MACOSX\/|__MACOSX\/.*|\.DS_STORE)$/i';

	/**
	 * Get a temporary file path
	 * @return mixed
	 */
	public static function getTempFilePath()
	{
		$fileName = uniqid('assets', true) . '.tmp';
		return IOHelper::createFile(blx()->path->getTempPath() . $fileName)->getRealPath();
	}
}

