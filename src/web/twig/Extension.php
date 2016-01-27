<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\web\twig;

use Craft;
use craft\app\dates\DateTime;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\Db;
use craft\app\helpers\Header;
use craft\app\helpers\StringHelper;
use craft\app\helpers\Template;
use craft\app\helpers\Url;
use craft\app\web\twig\tokenparsers\CacheTokenParser;
use craft\app\web\twig\tokenparsers\ExitTokenParser;
use craft\app\web\twig\tokenparsers\HeaderTokenParser;
use craft\app\web\twig\tokenparsers\HookTokenParser;
use craft\app\web\twig\tokenparsers\RegisterResourceTokenParser;
use craft\app\web\twig\tokenparsers\IncludeTranslationsTokenParser;
use craft\app\web\twig\tokenparsers\NamespaceTokenParser;
use craft\app\web\twig\tokenparsers\NavTokenParser;
use craft\app\web\twig\tokenparsers\PaginateTokenParser;
use craft\app\web\twig\tokenparsers\RedirectTokenParser;
use craft\app\web\twig\tokenparsers\RequireAdminTokenParser;
use craft\app\web\twig\tokenparsers\RequireEditionTokenParser;
use craft\app\web\twig\tokenparsers\RequireLoginTokenParser;
use craft\app\web\twig\tokenparsers\RequirePermissionTokenParser;
use craft\app\web\twig\tokenparsers\SwitchTokenParser;
use craft\app\web\twig\variables\Craft as CraftVariable;
use craft\app\web\View;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;

/**
 * Class Extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Extension extends \Twig_Extension
{
    // Properties
    // =========================================================================

    /**
     * @var View
     */
    protected $view;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * @inheritdoc
     */
    public function getNodeVisitors()
    {
        return [
            new NodeVisitor(),
        ];
    }

    /**
     * Returns the token parser instances to add to the existing list.
     *
     * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
     */
    public function getTokenParsers()
    {
        return [
            new CacheTokenParser(),
            new ExitTokenParser(),
            new HeaderTokenParser(),
            new HookTokenParser(),
            new RegisterResourceTokenParser('registerassetbundle', 'registerAssetBundle', false, true, false, false),
            new RegisterResourceTokenParser('registercss', 'registerCss', true, false, false, true),
            new RegisterResourceTokenParser('registerhirescss', 'registerHiResCss', true, false, false, true),
            new RegisterResourceTokenParser('registercssfile', 'registerCssFile', false, false, false, true),
            new RegisterResourceTokenParser('registercssresource', 'registerCssResource', false, false, false, true),
            new RegisterResourceTokenParser('registerjs', 'registerJs', true, true, true, false),
            new RegisterResourceTokenParser('registerjsfile', 'registerJsFile', false, true, false, true),
            new RegisterResourceTokenParser('registerjsresource', 'registerJsResource', false, true, false, true),
            new IncludeTranslationsTokenParser(),
            new NamespaceTokenParser(),
            new NavTokenParser(),
            new PaginateTokenParser(),
            new RedirectTokenParser(),
            new RequireAdminTokenParser(),
            new RequireEditionTokenParser(),
            new RequireLoginTokenParser(),
            new RequirePermissionTokenParser(),
            new SwitchTokenParser(),

            // Deprecated tags
            new RegisterResourceTokenParser('includeCss', 'registerCss', false, false, false, true, 'registercss'),
            new RegisterResourceTokenParser('includeHiResCss', 'registerHiResCss', true, false, false, true, 'registerhirescss'),
            new RegisterResourceTokenParser('includeCssFile', 'registerCssFile', true, false, false, true, 'registercssfile'),
            new RegisterResourceTokenParser('includeCssResource', 'registerCssResource', false, false, false, true, 'registercssresource'),
            new RegisterResourceTokenParser('includeJs', 'registerJs', false, true, true, false, 'registerjs'),
            new RegisterResourceTokenParser('includeJsFile', 'registerJsFile', true, true, false, true, 'registerjsfile'),
            new RegisterResourceTokenParser('includeJsResource', 'registerJsResource', false, true, false, true, 'registerjsresource'),

            new RegisterResourceTokenParser('includecss', 'registerCss', false, false, false, true, 'registercss'),
            new RegisterResourceTokenParser('includehirescss', 'registerHiResCss', true, false, false, true, 'registerhirescss'),
            new RegisterResourceTokenParser('includecssfile', 'registerCssFile', true, false, false, true, 'registercssfile'),
            new RegisterResourceTokenParser('includecssresource', 'registerCssResource', false, false, false, true, 'registercssresource'),
            new RegisterResourceTokenParser('includejs', 'registerJs', false, true, true, false, 'registerjs'),
            new RegisterResourceTokenParser('includejsfile', 'registerJsFile', true, true, false, true, 'registerjsfile'),
            new RegisterResourceTokenParser('includejsresource', 'registerJsResource', false, true, false, true, 'registerjsresource'),
        ];
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        $translateFilter = new \Twig_Filter_Method($this, 'translateFilter');
        $namespaceFilter = new \Twig_Filter_Function('\Craft::$app->getView()->namespaceInputs');
        $markdownFilter = new \Twig_Filter_Method($this, 'markdownFilter');

        return [
            'currency' => new \Twig_Filter_Function('\Craft::$app->getFormatter()->asCurrency'),
            'date' => new \Twig_Filter_Method($this, 'dateFilter', ['needs_environment' => true]),
            'datetime' => new \Twig_Filter_Function('\Craft::$app->getFormatter()->asDateTime'),
            'filesize' => new \Twig_Filter_Function('\Craft::$app->getFormatter()->asShortSize'),
            'filter' => new \Twig_Filter_Function('array_filter'),
            'group' => new \Twig_Filter_Method($this, 'groupFilter'),
            'id' => new \Twig_Filter_Function('\Craft::$app->getView()->formatInputId'),
            'indexOf' => new \Twig_Filter_Method($this, 'indexOfFilter'),
            'intersect' => new \Twig_Filter_Function('array_intersect'),
            'json_encode' => new \Twig_Filter_Method($this, 'jsonEncodeFilter'),
            'lcfirst' => new \Twig_Filter_Method($this, 'lcfirstFilter'),
            'literal' => new \Twig_Filter_Method($this, 'literalFilter'),
            'markdown' => $markdownFilter,
            'md' => $markdownFilter,
            'namespace' => $namespaceFilter,
            'ns' => $namespaceFilter,
            'namespaceInputName' => new \Twig_Filter_Function('\Craft::$app->getView()->namespaceInputName'),
            'namespaceInputId' => new \Twig_Filter_Function('\Craft::$app->getView()->namespaceInputId'),
            'number' => new \Twig_Filter_Function('\Craft::$app->getFormatter()->asDecimal'),
            'parseRefs' => new \Twig_Filter_Method($this, 'parseRefsFilter'),
            'percentage' => new \Twig_Filter_Function('\Craft::$app->getFormatter()->asPercent'),
            'replace' => new \Twig_Filter_Method($this, 'replaceFilter'),
            'translate' => $translateFilter,
            't' => $translateFilter,
            'ucfirst' => new \Twig_Filter_Method($this, 'ucfirstFilter'),
            'ucwords' => new \Twig_Filter_Function('ucwords'),
            'kebab' => new \Twig_Filter_Method($this, 'kebabFilter'),
            'without' => new \Twig_Filter_Method($this, 'withoutFilter'),
        ];
    }

    /**
     * Translates the given message.
     *
     * @param string $message  The message to be translated.
     * @param string $category the message category.
     * @param array  $params   The parameters that will be used to replace the corresponding placeholders in the message.
     * @param string $language The language code (e.g. `en-US`, `en`). If this is null, the current
     *                         [[\yii\base\Application::language|application language]] will be used.
     *
     * @return string the translated message.
     */
    public function translateFilter($message, $category = null, $params = null, $language = null)
    {
        // The front end site doesn't need to specify the category
        if (is_array($category)) {
            $language = $params;
            $params = $category;
            $category = 'site';
        } else if ($category === null) {
            $category = 'site';
        }

        if ($params === null) {
            $params = [];
        }

        try {
            return Craft::t($category, $message, $params, $language);
        } catch (InvalidConfigException $e) {
            return $message;
        }
    }

    /**
     * Uppercases the first character of a multibyte string.
     *
     * @param string $string The multibyte string.
     *
     * @return string The string with the first character converted to upercase.
     */
    public function ucfirstFilter($string)
    {
        return StringHelper::uppercaseFirst($string);
    }

    /**
     * Lowercases the first character of a multibyte string.
     *
     * @param string $string The multibyte string.
     *
     * @return string The string with the first character converted to lowercase.
     */
    public function lcfirstFilter($string)
    {
        return StringHelper::lowercaseFirst($string);
    }

    /**
     * Kebab-cases a string.
     *
     * @param string  $string            The string
     * @param string  $glue              The string used to glue the words together (default is a hyphen)
     * @param boolean $lower             Whether the string should be lowercased (default is true)
     * @param boolean $removePunctuation Whether punctuation marks should be removed (default is true)
     *
     * @return string The kebab-cased string
     */
    public function kebabFilter($string, $glue = '-', $lower = true, $removePunctuation = true)
    {
        return StringHelper::toKebabCase($string, $glue, $lower, $removePunctuation);
    }

    /**
     * This method will JSON encode a variable. We're overriding Twig's default implementation to set some stricter
     * encoding options on text/html/xml requests.
     *
     * @param mixed    $value   The value to JSON encode.
     * @param null|int $options Either null or a bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP,
     *                          JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES,
     *                          JSON_FORCE_OBJECT
     *
     * @return mixed The JSON encoded value.
     */
    public function jsonEncodeFilter($value, $options = null)
    {
        if ($options === null && (in_array(Header::getMimeType(),
                array('text/html', 'application/xhtml+xml')))
        ) {
            $options = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT;
        }

        return twig_jsonencode_filter($value, $options);
    }

    /**
     * Returns an array without certain values.
     *
     * @param array $arr
     * @param mixed $exclude
     *
     * @return array
     */
    public function withoutFilter($arr, $exclude)
    {
        $filteredArray = [];

        if (!is_array($exclude)) {
            $exclude = [$exclude];
        }

        foreach ($arr as $key => $value) {
            if (!in_array($value, $exclude)) {
                $filteredArray[$key] = $value;
            }
        }

        return $filteredArray;
    }

    /**
     * Parses a string for reference tags.
     *
     * @param string $str
     *
     * @return \Twig_Markup
     */
    public function parseRefsFilter($str)
    {
        $str = Craft::$app->getElements()->parseRefs($str);

        return Template::getRaw($str);
    }

    /**
     * Replaces Twig's |replace filter, adding support for passing in separate
     * search and replace arrays.
     *
     * @param mixed $str
     * @param mixed $search
     * @param mixed $replace
     *
     * @return mixed
     */
    public function replaceFilter($str, $search, $replace = null)
    {
        // Are they using the standard Twig syntax?
        if (is_array($search) && $replace === null) {
            return strtr($str, $search);
        } // Is this a regular expression?
        else if (preg_match('/^\/(.+)\/$/', $search)) {
            return preg_replace($search, $replace, $str);
        } else {
            // Otherwise use str_replace
            return str_replace($search, $replace, $str);
        }
    }

    /**
     * Extending Twig's |date filter so we can run any translations on the output.
     *
     * @param \Twig_Environment $env
     * @param                   $date
     * @param null              $format
     * @param null              $timezone
     * @param boolean           $translate Whether the formatted date string should be translated
     *
     * @return mixed|string
     */
    public function dateFilter(\Twig_Environment $env, $date, $format = null, $timezone = null, $translate = true)
    {
        // Let Twig do it's thing.
        $value = \twig_date_format_filter($env, $date, $format, $timezone);

        if ($translate) {
            $value = DateTimeHelper::translateDate($value);
        }

        return $value;
    }

    /**
     * Groups an array by a common property.
     *
     * @param array  $arr
     * @param string $item
     *
     * @return array
     */
    public function groupFilter($arr, $item)
    {
        $groups = [];

        $template = '{'.$item.'}';

        foreach ($arr as $key => $object) {
            $value = Craft::$app->getView()->renderObjectTemplate($template, $object);
            $groups[$value][] = $object;
        }

        return $groups;
    }

    /**
     * Returns the index of an item in a string or array, or -1 if it cannot be found.
     *
     * @param mixed $haystack
     * @param mixed $needle
     *
     * @return integer
     */
    public function indexOfFilter($haystack, $needle)
    {
        if (is_string($haystack)) {
            $index = strpos($haystack, $needle);
        } else if (is_array($haystack)) {
            $index = array_search($needle, $haystack);
        } else if (is_object($haystack) && $haystack instanceof \IteratorAggregate) {
            $index = false;

            foreach ($haystack as $i => $item) {
                if ($item == $needle) {
                    $index = $i;
                    break;
                }
            }
        }

        if (isset($index) && $index !== false) {
            return $index;
        } else {
            return -1;
        }
    }

    /**
     * Escapes commas and asterisks in a string so they are not treated as special characters in
     * [[Db::parseParam()]].
     *
     * @param string $value The param value.
     *
     * @return string The escaped param value.
     */
    public function literalFilter($value)
    {
        return Db::escapeParam($value);
    }

    /**
     * Parses text through Markdown.
     *
     * @param string $str
     *
     * @return \Twig_Markup
     */
    public function markdownFilter($str)
    {
        $html = Markdown::process($str);

        return Template::getRaw($html);
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            'actionUrl' => new \Twig_Function_Function('\\craft\\app\\helpers\\Url::getActionUrl'),
            'cpUrl' => new \Twig_Function_Function('\\craft\\app\\helpers\\Url::getCpUrl'),
            'ceil' => new \Twig_Function_Function('ceil'),
            'floor' => new \Twig_Function_Function('floor'),
            'getCsrfInput' => new \Twig_Function_Method($this, 'getCsrfInputFunction'),
            'getTranslations' => new \Twig_Function_Function('\Craft::$app->getView()->getTranslations'),
            'max' => new \Twig_Function_Function('max'),
            'min' => new \Twig_Function_Function('min'),
            'renderObjectTemplate' => new \Twig_Function_Function('\Craft::$app->getView()->renderObjectTemplate'),
            'round' => new \Twig_Function_Function('round'),
            'resourceUrl' => new \Twig_Function_Function('\\craft\\app\\helpers\\Url::getResourceUrl'),
            'shuffle' => new \Twig_Function_Method($this, 'shuffleFunction'),
            'siteUrl' => new \Twig_Function_Function('\\craft\\app\\helpers\\Url::getSiteUrl'),
            'url' => new \Twig_Function_Function('\\craft\\app\\helpers\\Url::getUrl'),
            // DOM event functions
            new \Twig_SimpleFunction('head', [$this->view, 'head']),
            new \Twig_SimpleFunction('beginBody', [$this->view, 'beginBody']),
            new \Twig_SimpleFunction('endBody', [$this->view, 'endBody']),
            // Deprecated functions
            new \Twig_SimpleFunction('getHeadHtml', [$this, 'getHeadHtml'],
                [true]),
            new \Twig_SimpleFunction('getFootHtml', [$this, 'getFootHtml'],
                [true]),
        ];
    }

    /**
     * Returns getCsrfInput() wrapped in a \Twig_Markup object.
     *
     * @return \Twig_Markup
     */
    public function getCsrfInputFunction()
    {
        $html = Craft::$app->getView()->getCsrfInput();

        return Template::getRaw($html);
    }

    /**
     * Shuffles an array.
     *
     * @param mixed $arr
     *
     * @return mixed
     */
    public function shuffleFunction($arr)
    {
        if ($arr instanceof \Traversable) {
            $arr = iterator_to_array($arr, false);
        } else {
            $arr = array_merge($arr);
        }

        shuffle($arr);

        return $arr;
    }

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array An array of global variables
     */
    public function getGlobals()
    {
        // Keep the 'blx' variable around for now
        $craftVariable = new CraftVariable();
        $globals['craft'] = $craftVariable;
        $globals['blx'] = $craftVariable;

        $globals['now'] = new DateTime(null, new \DateTimeZone(Craft::$app->getTimeZone()));
        $globals['loginUrl'] = Url::getUrl(Craft::$app->getConfig()->getLoginPath());
        $globals['logoutUrl'] = Url::getUrl(Craft::$app->getConfig()->getLogoutPath());

        $globals['POS_HEAD'] = View::POS_HEAD;
        $globals['POS_BEGIN'] = View::POS_BEGIN;
        $globals['POS_END'] = View::POS_END;
        $globals['POS_READY'] = View::POS_READY;
        $globals['POS_LOAD'] = View::POS_LOAD;

        if (Craft::$app->isInstalled() && !Craft::$app->getUpdates()->isCraftDbMigrationNeeded()) {
            $globals['siteName'] = Craft::$app->getSiteName();
            $globals['siteUrl'] = Craft::$app->getSiteUrl();

            $globals['currentUser'] = Craft::$app->getUser()->getIdentity();

            // Keep 'user' around so long as it's not hurting anyone.
            // Technically deprecated, though.
            $globals['user'] = $globals['currentUser'];

            $request = Craft::$app->getRequest();

            if (!$request->getIsConsoleRequest() && $request->getIsSiteRequest()) {
                foreach (Craft::$app->getGlobals()->getAllSets() as $globalSet) {
                    $globals[$globalSet->handle] = $globalSet;
                }
            }
        } else {
            $globals['siteName'] = null;
            $globals['siteUrl'] = null;
            $globals['user'] = null;
        }

        $request = Craft::$app->getRequest();

        if (!$request->getIsConsoleRequest() && $request->getIsCpRequest()) {
            $globals['CraftEdition'] = Craft::$app->getEdition();
            $globals['CraftPersonal'] = Craft::Personal;
            $globals['CraftClient'] = Craft::Client;
            $globals['CraftPro'] = Craft::Pro;
        }

        return $globals;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'craft';
    }

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * @deprecated in Craft 3.0. Use head() instead.
     * @return \Twig_Markup
     */
    public function getHeadHtml()
    {
        Craft::$app->getDeprecator()->log('getHeadHtml', 'getHeadHtml() has been deprecated. Use head() instead.');

        ob_start();
        ob_implicit_flush(false);
        $this->view->head();

        return Template::getRaw(ob_get_clean());
    }

    /**
     * @deprecated in Craft 3.0. Use endBody() instead.
     * @return \Twig_Markup
     */
    public function getFootHtml()
    {
        Craft::$app->getDeprecator()->log('getFootHtml', 'getFootHtml() has been deprecated. Use endBody() instead.');

        ob_start();
        ob_implicit_flush(false);
        $this->view->endBody();

        return Template::getRaw(ob_get_clean());
    }
}
