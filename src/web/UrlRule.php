<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\ArrayHelper;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UrlRule extends \yii\web\UrlRule
{
    // Properties
    // =========================================================================

    /**
     * @var array Pattern tokens that will be swapped out at runtime.
     */
    private static $_regexTokens;

    /**
     * @var array Parameters that should be passed to the controller.
     */
    public $params = [];

    // Public Methods
    // =========================================================================

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // Add support for a 'template' config option, which acts as a shortcut for templates/render?template=foo
        if (isset($config['template'])) {
            $config['route'] = 'templates/render';
            $config['params']['template'] = $config['template'];
            unset($config['template']);

            if (isset($config['variables'])) {
                $config['params']['variables'] = $config['variables'];
                unset($config['variables']);
            }
        }

        if (isset($config['pattern'])) {
            // Swap out any regex tokens in the pattern
            if (self::$_regexTokens === null) {
                $slugChars = ['.', '_', '-'];
                $slugWordSeparator = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;

                if ($slugWordSeparator !== '/' && !in_array($slugWordSeparator, $slugChars, true)) {
                    $slugChars[] = $slugWordSeparator;
                }

                // Reference: http://www.regular-expressions.info/unicode.html
                self::$_regexTokens = [
                    '{handle}' => '(?:[a-zA-Z][a-zA-Z0-9_]*)',
                    '{slug}' => '(?:[\p{L}\p{N}\p{M}'.preg_quote(implode($slugChars), '/').']+)',
                ];
            }

            $config['pattern'] = strtr($config['pattern'], self::$_regexTokens);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($manager, $request)
    {
        /** @var UrlManager $manager */
        $result = parent::parseRequest($manager, $request);

        // Is this a template route?
        if ($result !== false && $result[0] === 'templates/render') {
            // Nest any route params in the 'variables' param, so the controller gets them
            $result[1] = ['variables' => $result[1]];

            if (isset($result[1]['variables']['template'])) {
                $result[1]['template'] = $result[1]['variables']['template'];
                unset($result[1]['variables']['template']);
            }

            if (isset($result[1]['variables']['variables'])) {
                $result[1]['variables'] = ArrayHelper::merge($result[1]['variables'], $result[1]['variables']['variables']);
                unset($result[1]['variables']['variables']);
            }

            // Merge in any registered route params
            $result[1]['variables'] = array_merge($result[1]['variables'], $manager->getRouteParams());
        }

        return $result;
    }
}
