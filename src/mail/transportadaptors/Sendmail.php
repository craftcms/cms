<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */
namespace craft\app\mail\transportadaptors;

/**
 * Sendmail implements a Sendmail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Sendmail extends BaseTransportAdaptor
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return 'Sendmail';
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTransportConfig()
    {
        return [
            'class' => 'Swift_SendmailTransport',
        ];
    }
}
