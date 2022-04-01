<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

use craft\base\ConfigurableComponentInterface;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * TransportAdapterInterface defines the common interface to be implemented by Symfony Mailer transport adapter classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface TransportAdapterInterface extends ConfigurableComponentInterface
{
    /**
     * Returns the config array or an implementation of AbstractTransport object that should be passed to [[\craft\mail\Mailer::setTransport()]].
     *
     * @return array|AbstractTransport
     */
    public function defineTransport(): array|AbstractTransport;
}
