<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use yii\base\Object;

/** @noinspection PhpInternalEntityUsedInspection */

/**
 * Base Twig template class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 *
 * @method integer[] getDebugInfo()
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
     *
     * @throws \Twig_Error
     * @throws \Twig_Error_Runtime
     */
    protected function displayWithErrorHandling(array $context, array $blocks = [])
    {
        try {
            parent::displayWithErrorHandling($context, $blocks);
        } catch (\Twig_Error_Runtime $e) {
            if (Craft::$app->getConfig()->get('suppressTemplateErrors')) {
                // Just log it and move on
                Craft::$app->getErrorHandler()->logException($e);
            } else {
                throw $e;
            }
        }
    }
}
