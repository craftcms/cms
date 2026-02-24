<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use craft\helpers\ArrayHelper;
use craft\web\View;
use CraftCms\Cms\Support\Facades\Deprecator;
use Twig\DeprecatedCallableInfo;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

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
