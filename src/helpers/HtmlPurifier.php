<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\htmlpurifier\RelAttrLinkTypeDef;
use craft\htmlpurifier\VideoEmbedUrlDef;
use HTMLPurifier_Config;
use HTMLPurifier_Encoder;

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
     * @inheritdoc
     */
    public static function process($content, $config = null)
    {
        // Give array configs a definition ID so HTML Purifier caches the customized HTML definition,
        // rather than rebuilding it on every call. Its cache keys already account for the other HTML.*
        // directives. Config objects and closures can edit the definition themselves, so they aren't cached.
        if ($config === null || is_array($config)) {
            $config ??= [];
            $config['HTML.DefinitionID'] = 'craft';
            $config['HTML.DefinitionRev'] = self::HTML_DEFINITION_REV;
        }

        return parent::process($content, $config);
    }

    /**
     * Configures HTML Purifier with Craft’s defaults and HTML definition customizations.
     *
     * If `HTML.DefinitionID` is set, the customizations are only applied when the HTML definition isn’t
     * cached yet. Otherwise the definition is customized each time, and isn’t cached.
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
        $def = $config->get('HTML.DefinitionID') !== null
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
