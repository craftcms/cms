<?php
namespace Craft;

/**
 *
 */
abstract class EmailerType extends BaseEnum
{
	const Php      = 'php';
	const Sendmail = 'sendmail';
	const Smtp     = 'smtp';
	const Pop      = 'pop';
	const Gmail    = 'gmail';
}
