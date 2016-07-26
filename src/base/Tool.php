<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

use Craft;

/**
 * Tool is the base class for classes representing tools in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Tool extends Component implements ToolInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function iconValue()
    {
        return 'tool';
    }

    /**
     * @inheritdoc
     */
    public static function optionsHtml()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public static function buttonLabel()
    {
        return Craft::t('app', 'Go!');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function performAction($params = [])
    {
        return ['complete' => true];
    }
}
