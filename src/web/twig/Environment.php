<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function loadTemplate($name, $index = null)
    {
        try {
            return parent::loadTemplate($name, $index);
        } catch (\Twig_Error $e) {
            if (Craft::$app->getConfig()->get('suppressTemplateErrors')) {
                // Just log it and return an empty template
                Craft::$app->getErrorHandler()->logException($e);

                return Craft::$app->getView()->renderString('');
            }

            throw $e;
        }
    }

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
