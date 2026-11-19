<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Closure;
use Craft;
use craft\htmlpurifier\RelAttrLinkTypeDef;
use craft\htmlpurifier\SerializerDefinitionCache;
use craft\htmlpurifier\VideoEmbedUrlDef;
use HTMLPurifier_Config;
use HTMLPurifier_DefinitionCacheFactory;
use HTMLPurifier_Encoder;
use WeakMap;

/**
 * HtmlPurifier provides an ability to clean up HTML from any harmful code.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class HtmlPurifier extends \yii\helpers\HtmlPurifier
{
    /**
     * @param string $string
     * @return string
     */
    public static function cleanUtf8(string $string): string
    {
        return HTMLPurifier_Encoder::cleanUTF8($string);
    }

    /**
     * @param string $string
     * @param HTMLPurifier_Config $config
     * @return string
     */
    public static function convertToUtf8(string $string, HTMLPurifier_Config $config): string
    {
        /** @phpstan-ignore-next-line */
        return HTMLPurifier_Encoder::convertToUTF8($string, $config, null);
    }

    /**
     * @var int The revision of the HTML definition customizations made by [[configure()]].
     * Bump it whenever they change, so cached HTML definitions are rebuilt.
     */
    private const HTML_DEFINITION_REV = 1;

    /**
     * @var WeakMap<HTMLPurifier_Config,true>|null Configs that opted into HTML definition caching
     */
    private static ?WeakMap $cachedConfigs = null;

    /**
     * Passes markup through HTMLPurifier making it safe to output to end user.
     *
     * @param string $content The HTML content to purify
     * @param array|Closure|HTMLPurifier_Config|null $config The config to use for HTMLPurifier
     * @return string The purified HTML content
     */
    public static function process($content, $config = null)
    {
        // Cache the customized HTML definition for array configs, rather than rebuilding it on every call.
        // Config objects, closures, and subclasses can edit the definition themselves, so they're only
        // cached if they opt in via enableDefinitionCache().
        if (($config === null || is_array($config)) && static::class === self::class) {
            $config = HTMLPurifier_Config::create($config);
            self::enableDefinitionCache($config, 'craft');
        }

        // Same as parent::process(), except for the cache path
        $configInstance = HTMLPurifier_Config::create($config instanceof Closure ? null : $config);
        $configInstance->autoFinalize = false;
        $purifier = \HTMLPurifier::instance($configInstance);
        $purifier->config->set('Cache.SerializerPath', Craft::$app->getPath()->getHtmlPurifierCachePath());
        $purifier->config->set('Cache.SerializerPermissions', 0775);

        static::configure($configInstance);
        if ($config instanceof Closure) {
            $config($configInstance);
        }

        return $purifier->purify($content);
    }

    /**
     * Enables caching of the HTML definition for a config that will be passed to [[process()]].
     *
     * HTML Purifier rebuilds the HTML definition on every [[process()]] call unless it’s cached. Call this before
     * anything accesses the config’s HTML definition, and only edit the definition when
     * `$config->maybeGetRawHTMLDefinition()` returns one. When it returns `null`, the cached definition will
     * be used, which already includes the edits.
     *
     * ```php
     * $config = HTMLPurifier_Config::createDefault();
     * $config->autoFinalize = false;
     * HtmlPurifier::enableDefinitionCache($config, 'my-plugin-1');
     * if ($def = $config->maybeGetRawHTMLDefinition()) {
     *     $def->addAttribute('p', 'data-foo', 'Text');
     * }
     * $html = HtmlPurifier::process($html, $config);
     * ```
     *
     * Definitions are cached by `$id`, HTML Purifier’s version, and the config’s `HTML.*` settings. `$id` must
     * change whenever the definition edits do, such as by including a revision number or a hash of the
     * settings that determine them. Otherwise the old cached definition will keep being used.
     *
     * Cached definitions are stored in `storage/runtime/htmlpurifier/HTML/`, and can be cleared via the Caches utility.
     *
     * @param HTMLPurifier_Config $config The config. It must not be finalized yet.
     * @param string $id An ID that identifies all of the HTML definition edits that will be made.
     * @since 5.13.0
     */
    public static function enableDefinitionCache(HTMLPurifier_Config $config, string $id): void
    {
        $config->autoFinalize = false;
        $config->set('HTML.DefinitionID', $id);
        $config->set('HTML.DefinitionRev', self::HTML_DEFINITION_REV);
        self::$cachedConfigs ??= new WeakMap();
        self::$cachedConfigs[$config] = true;

        // process() sets these too, but the definition may be looked up before then
        $config->set('Cache.SerializerPath', Craft::$app->getPath()->getHtmlPurifierCachePath());
        $config->set('Cache.SerializerPermissions', 0775);

        // Write cache files atomically, unless the config chose another cache implementation or disabled it
        if ($config->get('Cache.DefinitionImpl') === 'Serializer') {
            HTMLPurifier_DefinitionCacheFactory::instance()->register('CraftSerializer', SerializerDefinitionCache::class);
            $config->set('Cache.DefinitionImpl', 'CraftSerializer');
        }
    }

    /**
     * Configures HTML Purifier with Craft’s defaults and HTML definition customizations.
     *
     * For configs passed to [[enableDefinitionCache()]], including the configs [[process()]] creates from
     * config arrays, the customizations are only applied when the HTML definition isn’t cached yet. Otherwise
     * the definition is customized each time, and isn’t cached.
     *
     * @param HTMLPurifier_Config $config
     */
    public static function configure($config): void
    {
        // Don't set alt attributes to filenames by default
        $config->set('Attr.DefaultImageAlt', '');
        $config->set('Attr.DefaultInvalidImageAlt', '');

        // allow HTML5 ID attributes by default
        $config->set('Attr.ID.HTML5', true);

        // Add support for some HTML5 elements
        // see https://github.com/mewebstudio/Purifier/issues/32#issuecomment-182502361
        // see https://gist.github.com/lluchs/3303693
        $def = isset(self::$cachedConfigs[$config])
            ? $config->maybeGetRawHTMLDefinition()
            : $config->getDefinition('HTML', true);

        if ($def) {
            /** @var \HTMLPurifier_HTMLDefinition $def */

            // Content model actually excludes several tags, not modelled here
            $def->addElement('address', 'Block', 'Flow', 'Common');
            $def->addElement('hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common');

            // http://developers.whatwg.org/grouping-content.html
            $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
            $def->addElement('figcaption', 'Inline', 'Flow', 'Common');

            // http://developers.whatwg.org/text-level-semantics.html
            $def->addElement('s', 'Inline', 'Inline', 'Common');
            $def->addElement('var', 'Inline', 'Inline', 'Common');
            $def->addElement('sub', 'Inline', 'Inline', 'Common');
            $def->addElement('sup', 'Inline', 'Inline', 'Common');
            $def->addElement('mark', 'Inline', 'Inline', 'Common');
            $def->addElement('wbr', 'Inline', 'Empty', 'Core');

            // http://developers.whatwg.org/edits.html
            $def->addElement('ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']);
            $def->addElement('del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']);

            // https://github.com/ezyang/htmlpurifier/issues/152#issuecomment-414192516
            $def->addAttribute('a', 'download', 'URI');

            // https://github.com/craftcms/ckeditor/issues/80
            $def->addAttribute('div', 'data-oembed-url',  new VideoEmbedUrlDef());

            $def->addElement('oembed', 'Block', 'Inline', 'Common');
            $def->addAttribute('oembed', 'url', new VideoEmbedUrlDef());

            $def->addAttribute('a', 'rel', new RelAttrLinkTypeDef('rel'));
        }
    }
}
