<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\web\twig;

use Craft;

/**
 * Base Twig template class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Environment extends \Twig_Environment
{
    /**
     * @inheritdoc
     */
    public function compileSource($source, $name = null)
    {
        Craft::beginProfile($name, __METHOD__);
        $result = parent::compileSource($source, $name);
        Craft::endProfile($name, __METHOD__);

        return $result;
    }
}
