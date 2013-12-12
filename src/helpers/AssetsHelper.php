<?php
namespace Craft;

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
	 * Get a temporary file path.
	 *
	 * @param string $extension extension to use. "tmp" by default.
	 * @return mixed
	 */
	public static function getTempFilePath($extension = 'tmp')
	{
		$extension = strpos($extension, '.') !== false ? pathinfo($extension, PATHINFO_EXTENSION) : $extension;
		$fileName = uniqid('assets', true) . '.' . $extension;

		return IOHelper::createFile(craft()->path->getTempPath() . $fileName)->getRealPath();
	}
}

