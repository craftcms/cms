<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterElementSourcesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RegisterElementSourcesEvent extends Event
{
    /**
     * @var string The context ('index' or 'modal').
     */
    public string $context;

    /**
     * @var array List of registered sources for the element type.
     */
    public array $sources = [];
}
