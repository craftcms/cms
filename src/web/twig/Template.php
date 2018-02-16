<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;

/**
 * Base Twig template class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 * @method int[] getDebugInfo()
 */
abstract class Template extends \Twig_Template
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function display(array $context, array $blocks = [])
    {
        $name = $this->getTemplateName();
        Craft::beginProfile($name, __METHOD__);
        parent::display($context, $blocks);
        Craft::endProfile($name, __METHOD__);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Displays the template.
     *
     * @param array $context
     * @param array $blocks
     * @throws \Twig_Error
     * @throws \Twig_Error_Runtime
     */
    protected function displayWithErrorHandling(array $context, array $blocks = [])
    {
        try {
            parent::displayWithErrorHandling($context, $blocks);
        } catch (\Twig_Error_Runtime $e) {
            if (Craft::$app->getConfig()->getGeneral()->suppressTemplateErrors) {
                // Just log it and move on
                Craft::$app->getErrorHandler()->logException($e);
            } else {
                throw $e;
            }
        }
    }
}
