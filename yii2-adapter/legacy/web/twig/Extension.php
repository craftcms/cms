<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\HtmlPurifier;
use craft\web\View;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Twig\DeprecatedCallableInfo;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use yii\base\InvalidConfigException;

/**
 * Legacy Twig extension shell.
 *
 * @deprecated in 6.0.0 register split extensions from `CraftCms\Cms\Twig\Extensions\*` instead.
 */
class Extension extends AbstractExtension implements GlobalsInterface
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('filterByValue', ArrayHelper::where(...), ['deprecation_info' => new DeprecatedCallableInfo('craftcms/cms', '3.5.0', 'where')]),
            new TwigFilter('firstWhere', ArrayHelper::firstWhere(...), ['deprecation_info' => new DeprecatedCallableInfo('craftcms/cms', '6.0.0')]),
            new TwigFilter('index', ArrayHelper::index(...), ['deprecation_info' => new DeprecatedCallableInfo('craftcms/cms', '6.0.0')]),
            new TwigFilter('purify', [$this, 'purifyFilter'], ['is_safe' => ['html']]),
            new TwigFilter('ucfirst', [$this, 'ucfirstFilter']),
            new TwigFilter('ucwords', [$this, 'ucwordsFilter'], ['needs_environment' => true]),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'view' => Craft::$app->getView(),
            'POS_READY' => View::POS_READY,
            'POS_LOAD' => View::POS_LOAD,
        ];
    }

    public function ucfirstFilter(mixed $string): string
    {
        Deprecator::log('ucfirst', 'The `|ucfirst` filter has been deprecated. Use `|capitalize` instead.');
        return mb_ucfirst((string)$string);
    }

    /**
     * @throws InvalidConfigException
     */
    public function purifyFilter(?string $html, array|string|null $config = null): ?string
    {
        if ($html === null) {
            return null;
        }

        if (is_string($config)) {
            $path = app()->configPath("craft/htmlpurifier/$config.json");
            $config = null;

            if (!is_file($path)) {
                Log::info("No HTML Purifier config found at $path.");
            } else {
                try {
                    $config = Json::decode(file_get_contents($path));
                } catch (InvalidArgumentException) {
                    Log::info("Invalid HTML Purifier config at $path.");
                }
            }
        }

        return HtmlPurifier::process($html, $config);
    }

    public function ucwordsFilter(TwigEnvironment $env, string $string): string
    {
        Deprecator::log('ucwords', 'The `|ucwords` filter has been deprecated. Use `|title` instead.');
        $charset = $env->getCharset();
        if ($charset) {
            return mb_convert_case($string, MB_CASE_TITLE, $charset);
        }
        return ucwords(strtolower($string));
    }
}
