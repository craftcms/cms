<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\console\Request as ConsoleRequest;
use craft\web\Request as WebRequest;
use yii\base\InvalidConfigException;

/**
 * Request trait.
 * This provides request methods that are common between craft\web\Request and craft\console\Request.
 *
 * @property string $scriptFilename The requested script name being used to access Craft (e.g. “index.php”).
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
trait RequestTrait
{
    /**
     * @var bool
     */
    public bool $isWebrootAliasSetDynamically = false;

    /**
     * @var bool
     */
    public bool $isWebAliasSetDynamically = false;

    /**
     * Returns the requested script name being used to access Craft (e.g. “index.php”).
     *
     * @return string
     * @throws InvalidConfigException
     */
    public function getScriptFilename(): string
    {
        /** @var WebRequest|ConsoleRequest $this */
        return basename($this->getScriptFile());
    }
}
