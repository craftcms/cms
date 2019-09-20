<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use Twig\Environment as TwigEnvironment;
use Twig\Error\Error;
use Twig\Extension\EscaperExtension;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Base Twig template class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Environment extends TwigEnvironment
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct(LoaderInterface $loader, array $options = [])
    {
        parent::__construct($loader, $options);
        $this->setDefaultEscaperStrategy();
    }

    /**
     * @inheritdoc
     */
    public function loadTemplate($name, $index = null)
    {
        try {
            /** @noinspection PhpInternalEntityUsedInspection */
            return parent::loadTemplate($name, $index);
        } catch (Error $e) {
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
    public function compileSource(Source $source)
    {
        Craft::beginProfile($source->getName(), __METHOD__);
        $result = parent::compileSource($source);
        Craft::endProfile($source->getName(), __METHOD__);

        return $result;
    }

    /**
     * @param mixed|null $strategy The escaper strategy to set. If null, it will be determined based on the template name.
     */
    public function setDefaultEscaperStrategy($strategy = null)
    {
        // don't have Twig escape HTML by default
        /** @var EscaperExtension $ext */
        $ext = $this->getExtension(EscaperExtension::class);
        $ext->setDefaultStrategy($strategy ?? [$this, 'getDefaultEscaperStrategy']);
    }

    /**
     * Returns the default escaper strategy to use based on the template name.
     *
     * @param string $name
     * @return string|false
     */
    public function getDefaultEscaperStrategy(string $name)
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['txt', 'text'], true) ? false : 'html';
    }
}
