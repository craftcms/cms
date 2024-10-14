<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * ApplyFieldSaveEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.13.0
 */
class ApplyFieldSaveEvent extends FieldEvent
{
    /**
     * @var array New field config data that is about to be applied.
     */
    public array $config;
}
