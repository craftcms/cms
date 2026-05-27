<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Closure;
use craft\htmlpurifier\RelAttrLinkTypeDef;
use craft\htmlpurifier\VideoEmbedUrlDef;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers;
use CraftCms\Cms\Support\Str;
use CraftCms\Yii2Adapter\HtmlPurifier\HtmlPurifierSanitizer;
use HTMLPurifier_Config;
use HTMLPurifier_Encoder;
use HTMLPurifier_HTMLDefinition;

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
     * @deprecated in 6.0.0. Use {@see HtmlSanitizers::sanitize()} instead.
     */
    public static function process($content, $config = null)
    {
        Deprecator::log('craft\\helpers\\HtmlPurifier::process', 'Calling `craft\\helpers\\HtmlPurifier::process()` is deprecated. Register an HTML sanitizer on `CraftCms\\Cms\\Support\\HtmlSanitizer\\HtmlSanitizers` instead.');

        if ($config instanceof Closure) {
            return parent::process($content, $config);
        }

        if ($config === null) {
            return parent::process($content);
        }

        if (!is_array($config)) {
            return parent::process($content, $config);
        }

        return app(HtmlSanitizers::class)->sanitize($content, new HtmlPurifierSanitizer($config));
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
        if ($def = $config->getDefinition('HTML', true)) {

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
