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
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Str;
use CraftCms\Yii2Adapter\HtmlPurifier\HtmlPurifierSanitizer;
use CraftCms\Yii2Adapter\LooseEnds\LegacyUsage;
use HTMLPurifier_Config;
use HTMLPurifier_DefinitionCacheFactory;
use HTMLPurifier_Encoder;
use HTMLPurifier_HTMLDefinition;
use Throwable;
use WeakMap;

/**
 * HtmlPurifier provides an ability to clean up HTML from any harmful code.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use {@see HtmlSanitizers} for HTML sanitization or {@see Str} for UTF-8 cleanup instead.
 */
class HtmlPurifier extends \yii\helpers\HtmlPurifier
{
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
     * @deprecated in 6.0.0. Use {@see HtmlSanitizers::sanitize()} instead.
     */
    public static function process($content, $config = null)
    {
        LegacyUsage::record('craft\\helpers\\HtmlPurifier::process', 'Calling `craft\\helpers\\HtmlPurifier::process()` is deprecated. Register an HTML sanitizer with `CraftCms\\Cms\\Support\\Facades\\HtmlSanitizers::extend()` instead.');

        // The sanitizer caches the customized HTML definition for array configs, rather than rebuilding it on
        // every call. Subclasses can edit the definition in configure(), so they only use it for array configs.
        if (is_array($config) || ($config === null && static::class === self::class)) {
            return HtmlSanitizers::sanitize($content, new HtmlPurifierSanitizer($config));
        }

        // Same as parent::process(), except for the cache path. Config objects and closures can edit the
        // definition themselves, so it's only cached if they opt in via enableDefinitionCache().
        $configInstance = HTMLPurifier_Config::create($config instanceof Closure ? null : $config);
        $configInstance->autoFinalize = false;
        $purifier = \HTMLPurifier::instance($configInstance);
        self::useCraftCache($purifier->config);

        static::configure($configInstance);
        if ($config instanceof Closure) {
            $config($configInstance);
        }

        return $purifier->purify($content);
    }

    /**
     * @param string $string
     * @return string
     * @deprecated in 6.0.0.
     */
    public static function cleanUtf8(string $string): string
    {
        return HTMLPurifier_Encoder::cleanUTF8($string);
    }

    /**
     * @param string $string
     * @param HTMLPurifier_Config $config
     * @return string
     * @deprecated in 6.0.0. Use {@see Str::convertToUtf8()} instead.
     */
    public static function convertToUtf8(string $string, HTMLPurifier_Config $config): string
    {
        return Str::convertToUtf8($string, strtolower($config->get('Core.Encoding')));
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
     * @deprecated in 6.0.0. Use {@see HtmlSanitizers} for HTML sanitization instead.
     */
    public static function enableDefinitionCache(HTMLPurifier_Config $config, string $id): void
    {
        $config->autoFinalize = false;
        $config->set('HTML.DefinitionID', $id);
        $config->set('HTML.DefinitionRev', self::HTML_DEFINITION_REV);
        self::$cachedConfigs ??= new WeakMap();
        self::$cachedConfigs[$config] = true;

        // process() calls this too, but the definition may be looked up before then
        self::useCraftCache($config);
    }

    /**
     * Configures a config to cache definitions in Craft’s cache path.
     *
     * @internal
     */
    public static function useCraftCache(HTMLPurifier_Config $config): void
    {
        $config->set('Cache.SerializerPath', self::cachePath());
        $config->set('Cache.SerializerPermissions', 0775);

        // Write cache files atomically, and tolerate ones removed by clearing caches, unless the config chose
        // another cache implementation or disabled caching
        if ($config->get('Cache.DefinitionImpl') === 'Serializer') {
            HTMLPurifier_DefinitionCacheFactory::instance()->register('CraftSerializer', SerializerDefinitionCache::class);
            $config->set('Cache.DefinitionImpl', 'CraftSerializer');
        }
    }

    /**
     * Returns the cache path, creating it if possible.
     *
     * Creation failures are ignored, so purification still works when the runtime folder is read-only and
     * caching is disabled. HTML Purifier reports them itself if it tries to cache a definition.
     */
    private static function cachePath(): string
    {
        $path = Craft::$app->getPath()->getHtmlPurifierCachePath(false);
        if (!is_dir($path)) {
            try {
                FileHelper::createDirectory($path);
            } catch (Throwable) {
            }
        }
        return $path;
    }

    /**
     * @inheritdoc
     * @deprecated in 6.0.0. Use {@see HtmlSanitizers::defaults()} or a custom sanitizer registration instead.
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
            /** @var HTMLPurifier_HTMLDefinition $def */
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
