<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Env;
use yii\base\InvalidConfigException;

/**
 * Request trait.
 * This provides request methods that are common between craft\web\Request and craft\console\Request.
 *
 * @property string $scriptFilename The requested script name being used to access Craft (e.g. “index.php”).
 * @property-read bool $isWebRequest Whether this is a web request.
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
        return basename($this->getScriptFile());
    }

    /**
     * Returns whether this is a web request.
     *
     * @return bool
     * @since 5.5.0
     */
    public function getIsWebRequest(): bool
    {
        return !app()->runningInConsole();
    }

    protected function setWebAlias(string $fallback): void
    {
        if ($webUrl = Env::get('CRAFT_WEB_URL')) {
            Aliases::set('@web', (string) $webUrl);

            return;
        }

        if (Aliases::get('@web', false) !== false) {
            return;
        }

        if ($webUrl = (string) config('app.url')) {
            Aliases::set('@web', $webUrl);

            return;
        }

        Aliases::set('@web', $fallback);
        $this->isWebAliasSetDynamically = true;
    }
}
