<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * DefineMetadataEvent is used to define metadata.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.0
 */
class DefineMetadataEvent extends Event
{
    /**
     * @var array The metadata, with keys representing the labels. The values can either be strings or
     * callables. If a value is `false`, it will be omitted.
     */
    public array $metadata = [];
}
