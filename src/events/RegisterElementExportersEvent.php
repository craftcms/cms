<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterElementExportersEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class RegisterElementExportersEvent extends Event
{
    /**
     * @var string The selected source’s key
     */
    public string $source;

    /**
     * @var array List of registered exporters for the element type.
     */
    public array $exporters = [];
}
