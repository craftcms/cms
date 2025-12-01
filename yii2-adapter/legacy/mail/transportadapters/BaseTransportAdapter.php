<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use craft\base\ConfigurableComponent;
use yii\base\Model;

/**
 * Php implements a PHP Mail transport adapter into Craft’s mailer.
 *
 * @mixin Model
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class BaseTransportAdapter extends ConfigurableComponent implements TransportAdapterInterface
{
}
