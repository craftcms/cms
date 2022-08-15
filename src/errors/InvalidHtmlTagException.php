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
     * @var string|null The tag type
     */
    public ?string $type = null;

    /**
     * @var array|null The tag attributes
     */
    public ?array $attributes = null;

    /**
     * @var int|null The tag’s starting position
     */
    public ?int $start = null;

    /**
     * @var int|null The tag’s inner HTML starting position
     */
    public ?int $htmlStart = null;

    /**
     * Constructor.
     *
     * @param string $message The error message
     * @param string|null $type The tag type
     * @param array|null $attributes The tag attributes
     * @param int|null $start The tag’s starting position
     * @param int|null $htmlStart The tag’s inner HTML starting position
     */
    public function __construct(string $message, ?string $type = null, ?array $attributes = null, ?int $start = null, ?int $htmlStart = null)
    {
        $this->type = $type;
        $this->attributes = $attributes;
        $this->start = $start;
        $this->htmlStart = $htmlStart;

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
