<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use craft\base\ConfigurableComponentInterface;

/**
 * TransportAdapterInterface defines the common interface to be implemented by SwiftMailer transport adapter classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface TransportAdapterInterface extends ConfigurableComponentInterface
{
    /**
     * Returns the config array or \Swift_Transport object that should be passed to [[\craft\mail\Mailer::setTransport()]].
     *
     * @return array|\Swift_Transport
     */
    public function defineTransport();
}
