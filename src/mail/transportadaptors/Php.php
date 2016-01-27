<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */
namespace craft\app\mail\transportadaptors;

use Craft;

/**
 * Php implements a PHP Mail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Php extends BaseTransportAdaptor
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return 'PHP Mail';
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTransportConfig()
    {
        return [
            'class' => 'Swift_MailTransport',
        ];
    }
}
