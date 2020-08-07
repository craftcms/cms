<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use craft\base\ElementInterface;

/**
 * Class UnsupportedSiteException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.29
 */
class UnsupportedSiteException extends ElementException
{
    /**
     * @var int The site ID that the element doesn’t support.
     */
    public $siteId;

    /**
     * Constructor.
     *
     * @param ElementInterface $element The element
     * @param int $siteId The site ID that the element doesn’t support
     * @param string|null $message The error message
     * @param int $code The error code
     */
    public function __construct(ElementInterface $element, int $siteId, string $message = null, int $code = 0)
    {
        $this->siteId = $siteId;

        if ($message === null) {
            $message = "The element “{$element}” doesn’t support site $siteId.";
        }

        parent::__construct($element, $message, $code);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Unsupported site';
    }
}
