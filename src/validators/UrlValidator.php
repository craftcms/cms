<?php
namespace Blocks;

/**
 *
 */
class UrlValidator extends \CUrlValidator
{
	/**
	 * Override the $pattern regex so that a TLD is not required
	 */
	public $pattern = '/^{schemes}:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)?[^\s]*$/i';
}
