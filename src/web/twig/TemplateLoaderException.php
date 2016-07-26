<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig;

/**
 * Class TemplateLoaderException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class TemplateLoaderException extends \Twig_Error_Loader
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $template;

    // Public Methods
    // =========================================================================

    /**
     * @param string $template The requested template
     * @param string $message  The exception message
     */
    public function __construct($template, $message)
    {
        $this->template = $template;
        parent::__construct($message);
    }
}
