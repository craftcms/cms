<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */
namespace craft\app\mail\transportadaptors;

use craft\app\base\SavableComponent;

/**
 * Php implements a PHP Mail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class BaseTransportAdaptor extends SavableComponent implements TransportAdaptorInterface
{
}
