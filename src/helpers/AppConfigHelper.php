<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\mail\Mailer;
use craft\app\mail\transportadaptors\BaseTransportAdaptor;
use craft\app\mail\transportadaptors\TransportAdaptorInterface;
use craft\app\models\MailSettings;

/**
 * Class DateTimeHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AppConfigHelper
{
    /**
     * Creates a mailer component based on the given mail settings and adaptor.
     *
     * @param MailSettings              $settings
     * @param TransportAdaptorInterface $adapter
     *
     * @return Mailer
     */
    public static function createMailer($settings, $adapter)
    {
        /** @var BaseTransportAdaptor $adapter */
        $config = [
            'class' => Mailer::class,
            'from' => [$settings->fromEmail => $settings->fromName],
            'template' => $settings->template,
            'transport' => $adapter->getTransportConfig()
        ];

        /** @var Mailer $mailer */
        $mailer = Craft::createObject($config);

        return $mailer;
    }
}
