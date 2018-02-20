<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use craft\base\Model;

/**
 * SystemMessage represents a system email message.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SystemMessage extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string|null Unique key for the message
     */
    public $key;

    /**
     * @var string|null Heading to be shown on the settings page for this message
     */
    public $heading;

    /**
     * @var string|null Subject template
     */
    public $subject;

    /**
     * @var string|null Body template
     */
    public $body;
}
