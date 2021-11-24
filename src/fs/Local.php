<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs;

use craft\base\FsInterface;
use craft\base\LocalFsInterface;

/**
 * Local is the class for local file system operations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Local implements LocalFsInterface, FsInterface
{
    public function fileExists(string $path): bool
    {
        return true;
    }
}
