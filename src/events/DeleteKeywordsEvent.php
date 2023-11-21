<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use yii\base\Event;

/**
 * CancelableEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DeleteKeywordsEvent extends Event
{
    /**
     * @var ElementInterface The element being indexed
     */
    public ElementInterface $element;

    /**
     * @var FieldInterface[] List fields whose keywords are being updated
     */
    public array $updateFields = [];

    /**
     * @var int[] List field IDs to be left alone in the {{searchindexes}} table
     */
    public array $ignoreFieldIds = [];
}
