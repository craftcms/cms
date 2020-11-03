<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use Twig\Error\Error;
use Twig\Error\RuntimeError;
use Twig\Template as TwigTemplate;

/**
 * Base Twig template class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @method int[] getDebugInfo()
 * @deprecated in 3.3.0
 */
abstract class Template extends TwigTemplate
{
    /**
     * Displays the template.
     *
     * @param array $context
     * @param array $blocks
     * @throws Error
     * @throws RuntimeError
     */
    protected function displayWithErrorHandling(array $context, array $blocks = [])
    {
        try {
            parent::displayWithErrorHandling($context, $blocks);
        } catch (RuntimeError $e) {
            if (Craft::$app->getConfig()->getGeneral()->suppressTemplateErrors) {
                // Just log it and move on
                Craft::$app->getErrorHandler()->logException($e);
            } else {
                throw $e;
            }
        }
    }
}
