<?php
namespace Blocks;

/**
 *
 */
class UrlValidator extends \CUrlValidator
{
	public $requireSchema = true;
	const optionalSchemaUrlRegex = '/^({schemes}:\/\/)?(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i';

	/**
	 * @param $object
	 * @param $attribute
	 */
	protected function validateAttribute($object, $attribute)
	{
		if (!$this->requireSchema)
			$this->pattern = self::optionalSchemaUrlRegex;

		parent::validateAttribute($object, $attribute);
	}
}
