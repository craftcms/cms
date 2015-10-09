<?php
namespace craft\app\mail\transportadaptors;

use craft\app\base\SavableComponentInterface;

/**
 * TransportAdaptorInterface defines the common interface to be implemented by SwiftMailer transport adaptor classes.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.base
 * @since     3.0
 */
interface TransportAdaptorInterface extends SavableComponentInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the config array that should be passed to [[\craft\app\mail\Mailer::setTransport()]].
     *
     * Note that the return value will be stored in the database as JSON,
     * so you cannot create an actual \Swift_Transport object here.
     *
     * @return array The transport config
     */
    public function getTransportConfig();
}
