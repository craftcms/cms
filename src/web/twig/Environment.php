<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig;

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
            /** @noinspection PhpInternalEntityUsedInspection */
            return parent::loadTemplate($name, $index);
        } catch (\Twig_Error $e) {
            if (Craft::$app->getConfig()->getGeneral()->suppressTemplateErrors) {
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
    public function compileSource(\Twig_Source $source)
    {
        Craft::beginProfile($source->getName(), __METHOD__);
        $result = parent::compileSource($source);
        Craft::endProfile($source->getName(), __METHOD__);

        return $result;
    }
}
