<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use Throwable;
use yii\base\Exception;

/**
 * Deprecation Error Exception
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.18
 */
class DeprecationException extends Exception
{
    /**
     * Constructor
     *
     * @param string $message
     * @param string|null $file
     * @param int|null $line
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', ?string $file = null, ?int $line = null, int $code = 0, ?Throwable $previous = null)
    {
        if ($file !== null) {
            $this->file = $file;
        }

        if ($line !== null) {
            $this->line = $line;
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Deprecation Error';
    }
}
