<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\InvalidArgumentException;

/**
 * InvalidHtmlTagException represents an invalid HTML tag encountered via [[\craft\helpers\Html::parseTag()]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.27
 */
class InvalidHtmlTagException extends InvalidArgumentException
{
    /**
     * Constructor.
     *
     * @param string $message The error message
     * @param string|null $type The tag type
     * @param array|null $attributes The tag attributes
     * @param int|null $start The tag’s starting position
     * @param int|null $htmlStart The tag’s inner HTML starting position
     */
    public function __construct(string $message, public ?string $type = null, public ?array $attributes = null, public ?int $start = null, public ?int $htmlStart = null)
    {
        parent::__construct($message);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid HTML tag';
    }
}
