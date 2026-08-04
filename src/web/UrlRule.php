<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UrlRule extends \yii\web\UrlRule
{
    /**
     * Returns an array of regex tokens supported by URL rules.
     *
     * @return array
     * @since 5.6.0
     */
    public static function regexTokens(): array
    {
        $slugChars = ['.', '_', '-'];
        $slugWordSeparator = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;
        if ($slugWordSeparator !== '/' && !in_array($slugWordSeparator, $slugChars, true)) {
            $slugChars[] = $slugWordSeparator;
        }

        return [
            '{handle}' => sprintf('(?:%s)', HandleValidator::$handlePattern),
            // Reference: http://www.regular-expressions.info/unicode.html
            '{slug}' => sprintf('(?:[\p{L}\p{N}\p{M}%s]+)', preg_quote(implode($slugChars), '/')),
            '{uid}' => sprintf('(?:%s)', StringHelper::UUID_PATTERN),
        ];
    }

    /**
     * @var array Pattern tokens that will be swapped out at runtime.
     */
    private static array $_regexTokens;

    /**
     * @var array Parameters that should be passed to the controller.
     */
    public array $params = [];

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // Add support for a 'template' config option, which acts as a shortcut for templates/render?template=foo
        if (array_key_exists('template', $config)) {
            $config['route'] = 'templates/render';
            $config['params']['template'] = (string)$config['template'];
            unset($config['template']);

            if (isset($config['variables'])) {
                $config['params']['variables'] = $config['variables'];
                unset($config['variables']);
            }
        }

        if (isset($config['pattern'])) {
            // Swap out any regex tokens in the pattern
            if (!isset(self::$_regexTokens)) {
                self::$_regexTokens = static::regexTokens();
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
