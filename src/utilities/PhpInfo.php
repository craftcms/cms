<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;

/**
 * PhpInfo represents a PhpInfo dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PhpInfo extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'PHP Info');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'php-info';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/info-circle.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_components/utilities/PhpInfo', [
            'phpInfo' => self::_phpInfo(),
        ]);
    }

    /**
     * Parses and returns the PHP info.
     *
     * @return array
     */
    private static function _phpInfo(): array
    {
        ob_start();
        phpinfo(-1);
        $phpInfoStr = ob_get_clean();

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

        $sections = explode('<h2>', strip_tags($phpInfoStr, '<h2><th><td>'));
        unset($sections[0]);

        $phpInfo = [];
        $security = Craft::$app->getSecurity();

        foreach ($sections as $section) {
            $heading = substr($section, 0, strpos($section, '</h2>'));

            if (preg_match_all('#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#', $section, $matches, PREG_SET_ORDER) !== 0) {
                /** @var array[] $matches */
                foreach ($matches as $row) {
                    if (!isset($row[2])) {
                        continue;
                    }

                    if (!isset($row[3]) || $row[2] === $row[3]) {
                        $value = $row[2];
                    } else {
                        $value = array_slice($row, 2);
                    }

                    $name = $row[1];
                    $phpInfo[$heading][$name] = $security->redactIfSensitive($name, $value);
                }
            }
        }

        return $phpInfo;
    }
}
