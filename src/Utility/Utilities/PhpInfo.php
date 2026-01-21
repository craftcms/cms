<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;

/**
 * PhpInfo represents a PhpInfo dashboard widget.
 */
final class PhpInfo extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('PHP Info');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function id(): string
    {
        return 'php-info';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function isSelectable(): bool
    {
        return function_exists('phpinfo');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function icon(): string
    {
        return 'circle-info';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/utilities/PhpInfo.twig', [
            'phpInfo' => self::phpInfo(),
        ]);
    }

    /**
     * Parses and returns the PHP info.
     */
    private static function phpInfo(): array
    {
        // Remove any arrays from $_ENV and $_SERVER to get around an "Array to string conversion" error
        $envVals = [];
        $serverVals = [];

        foreach ($_ENV as $key => $value) {
            if (is_array($value)) {
                $envVals[$key] = $value;
                $_ENV[$key] = 'Array';
            }
        }

        foreach ($_SERVER as $key => $value) {
            if (is_array($value)) {
                $serverVals[$key] = $value;
                $_SERVER[$key] = 'Array';
            }
        }

        ob_start();
        phpinfo(INFO_ALL);
        $phpInfoStr = ob_get_clean();

        // Put the original $_ENV and $_SERVER values back
        foreach ($envVals as $key => $value) {
            $_ENV[$key] = $value;
        }
        foreach ($serverVals as $key => $value) {
            $_SERVER[$key] = $value;
        }

        $replacePairs = [
            '#^.*<body>(.*)</body>.*$#ms' => '$1',
            '#<h2>PHP License</h2>.*$#ms' => '',
            '#<h1>Configuration</h1>#' => '',
            "#\r?\n#" => '',
            '#</(h1|h2|h3|tr)>#' => '</$1>'."\n",
            '# +<#' => '<',
            "#[ \t]+#" => ' ',
            '#&nbsp;#' => ' ',
            '#  +#' => ' ',
            '# class=".*?"#' => '',
            '%&#039;%' => ' ',
            '#<tr>(?:.*?)"src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#' => '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'."\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
            '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#' => '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
            '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#' => '<tr><td>Zend Engine</td><td>$2</td></tr>'."\n".'<tr><td>Zend Egg</td><td>$1</td></tr>',
            '# +#' => ' ',
            '#<tr>#' => '%S%',
            '#</tr>#' => '%E%',
        ];

        $phpInfoStr = preg_replace(array_keys($replacePairs), array_values($replacePairs), $phpInfoStr);

        $sections = explode('<h2>', strip_tags((string) $phpInfoStr, '<h2><th><td>'));
        unset($sections[0]);

        $phpInfo = [];

        foreach ($sections as $section) {
            $heading = substr($section, 0, strpos($section, '</h2>'));

            if (preg_match_all('#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#', $section, $matches, PREG_SET_ORDER) === 0) {
                continue;
            }

            foreach ($matches as $row) {
                if (! isset($row[2])) {
                    continue;
                }

                [, $name, $value] = $row;

                $phpInfo[$heading][$name] = Security::redactIfSensitive($name, $value);
            }
        }

        return $phpInfo;
    }
}
