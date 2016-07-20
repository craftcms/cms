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
    // Properties
    // =========================================================================

    /**
     * @var boolean
     */
    protected $safeMode;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param \Twig_LoaderInterface $loader
     * @param array                 $options
     */
    public function __construct(\Twig_LoaderInterface $loader, array $options)
    {
        $options = array_merge([
            'safe_mode' => false,
        ], $options);

        $this->safeMode = $options['safe_mode'];

        parent::__construct($loader, $options);
    }

    /**
     * @return mixed
     */
    public function isSafeMode()
    {
        return $this->safeMode;
    }

    /**
     * @param $safeMode
     */
    public function setSafeMode($safeMode)
    {
        $this->safeMode = $safeMode;
    }

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
