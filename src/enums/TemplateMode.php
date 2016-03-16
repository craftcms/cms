<?php
namespace Craft;

/**
 * The TemplateMode class is an abstract class that defines whether the {@link TemplateService}
 * is in a Control Panel or Site context.
 *
 * This class is a poor man's version of an enum, since PHP does not have support for native enumerations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.enums
 * @since     2.6
 */
abstract class TemplateMode
{
	// Constants
	// =========================================================================

	const CP = 'cp';
	const Site = 'site';
}
