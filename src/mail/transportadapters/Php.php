<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\mail\transportadapters;

/**
 * Php implements a PHP Mail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Php extends BaseTransportAdapter
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'PHP Mail';
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function defineTransport()
    {
        return [
            'class' => \Swift_MailTransport::class,
        ];
    }
}
