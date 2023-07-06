<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Entrify event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.16
 */
class EntrifyEvent extends Event
{
    /**
     * @var string Element type being entrified
     */
    public string $elementType;

    /**
     * @var array Array of from and to element identifiers
     */
    public array $elementGroup = [];

    /**
     * @var array The array of fields that were entrified
     */
    public array $fields = [];
}
