<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Export event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ExportEvent extends Event
{
    // Properties
    // =========================================================================

    public $header;
    public $results;
    public $format;
    public $elementType;
    public $sourceKey;
    public $criteria;
    public $query;
    public $contents;
    public $filename;
    public $mimeType;

}
