<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\elements;

use craft\base\Element;

/**
 * Class ExampleElement.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.2
 */
class ExampleElement extends Element
{
    // Public Properties
    // =========================================================================

    /**
     * @var
     */
    public $uriFormat;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getUriFormat()
    {
        return $this->uriFormat;
    }
}
