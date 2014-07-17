<?php
namespace Craft;

/**
 * Class EmailerType
 *
 * @abstract
 * @package craft.app.enums
 */
abstract class EmailerType extends BaseEnum
{
	const Php      = 'php';
	const Sendmail = 'sendmail';
	const Smtp     = 'smtp';
	const Pop      = 'pop';
	const Gmail    = 'gmail';
}
