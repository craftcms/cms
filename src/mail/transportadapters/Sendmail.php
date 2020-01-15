<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail\transportadapters;

/**
 * Sendmail implements a Sendmail transport adapter into Craft’s mailer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Sendmail extends BaseTransportAdapter
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Sendmail';
    }

    /**
     * @inheritdoc
     */
    public function defineTransport()
    {
        return [
            'class' => \Swift_SendmailTransport::class,
        ];
    }
}
