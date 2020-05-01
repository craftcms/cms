<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Twig\Error\LoaderError;

/**
 * Class TemplateLoaderException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class TemplateLoaderException extends LoaderError
{
    /**
     * @var string|null
     */
    public $template;

    /**
     * @param string $template The requested template
     * @param string $message The exception message
     */
    public function __construct(string $template, string $message)
    {
        $this->template = $template;
        parent::__construct($message);
    }
}
