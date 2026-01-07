<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _layouts/base */
class __TwigTemplate_e0d3158689e836babe698cb7dec52f67 extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'head' => $this->block_head(...),
            'body' => $this->block_body(...),
            'foot' => $this->block_foot(...),
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_layouts/base');
        // line 1
        $context['systemName'] = $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'app', []), 'getSystemName', [], 'method'), 'site');
        // line 2
        $context['docTitle'] = ((array_key_exists('docTitle', $context)) ? ((isset($context['docTitle']) || array_key_exists('docTitle', $context) ? $context['docTitle'] : (function () {
            throw new RuntimeError('Variable "docTitle" does not exist.', 2, $this->source);
        })())) : (twig_striptags((isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
            throw new RuntimeError('Variable "title" does not exist.', 2, $this->source);
        })()))));
        // line 3
        $context['orientation'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 3, $this->source);
        })()), 'app', []), 'locale', []), 'getOrientation', [], 'method');
        // line 4
        $context['a11yDefaults'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
        })()), 'app', []), 'config', []), 'general', []), 'accessibilityDefaults', []);
        // line 5
        $context['requestedSite'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 5, $this->source);
        })()), 'cp', []), 'requestedSite', []);
        // line 6
        echo '
';
        // line 7
        $context['bodyClass'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((($context['bodyClass']) ?? ([]))), [0 =>         // line 8
(isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
    throw new RuntimeError('Variable "orientation" does not exist.', 8, $this->source);
})()), 1 => ((! (((craft\helpers\Template::attribute($this->env, $this->source,         // line 9
    ($context['currentUser'] ?? null), 'getPreference', [0 => 'alwaysShowFocusRings'], 'method', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', [0 => 'alwaysShowFocusRings'], 'method') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', [0 => 'alwaysShowFocusRings'], 'method')) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'alwaysShowFocusRings', [], 'array', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'alwaysShowFocusRings', [], 'array') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'alwaysShowFocusRings', [], 'array')) : (false))))) ? ('reduce-focus-visibility') : ('')), 2 => (((((craft\helpers\Template::attribute($this->env, $this->source,         // line 10
        ($context['currentUser'] ?? null), 'getPreference', [0 => 'useShapes'], 'method', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', [0 => 'useShapes'], 'method') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', [0 => 'useShapes'], 'method')) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'useShapes', [], 'array', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'useShapes', [], 'array') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'useShapes', [], 'array')) : (false))))) ? ('use-shapes') : ('')), 3 => (((((craft\helpers\Template::attribute($this->env, $this->source,         // line 11
            ($context['currentUser'] ?? null), 'getPreference', [0 => 'underlineLinks'], 'method', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', [0 => 'underlineLinks'], 'method') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', [0 => 'underlineLinks'], 'method')) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'underlineLinks', [], 'array', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'underlineLinks', [], 'array') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'underlineLinks', [], 'array')) : (false))))) ? ('underline-links') : ('')), 4 => ((        // line 12
                (isset($context['requestedSite']) || array_key_exists('requestedSite', $context) ? $context['requestedSite'] : (function () {
                    throw new RuntimeError('Variable "requestedSite" does not exist.', 12, $this->source);
                })())) ? (('site--'.craft\helpers\Template::attribute($this->env, $this->source, (isset($context['requestedSite']) || array_key_exists('requestedSite', $context) ? $context['requestedSite'] : (function () {
                    throw new RuntimeError('Variable "requestedSite" does not exist.', 12, $this->source);
                })()), 'handle', []))) : ('')), ]));
        // line 15
        $context['sidebarState'] = (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true), 'request', [], 'any', false, true), 'rawCookies', [], 'any', false, true), 'value', [0 => (('Craft-'.craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
        })()), 'app', []), 'systemUid', [])).':sidebar')], 'method', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true), 'request', [], 'any', false, true), 'rawCookies', [], 'any', false, true), 'value', [0 => (('Craft-'.craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
        })()), 'app', []), 'systemUid', [])).':sidebar')], 'method') === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true), 'request', [], 'any', false, true), 'rawCookies', [], 'any', false, true), 'value', [0 => (('Craft-'.craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
        })()), 'app', []), 'systemUid', [])).':sidebar')], 'method')) : ('expanded'));
        // line 16
        $context['bodyAttributes'] = $this->extensions['craft\web\twig\Extension']->mergeFilter(['class' =>         // line 17
(isset($context['bodyClass']) || array_key_exists('bodyClass', $context) ? $context['bodyClass'] : (function () {
    throw new RuntimeError('Variable "bodyClass" does not exist.', 17, $this->source);
})()), 'dir' =>         // line 18
(isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
    throw new RuntimeError('Variable "orientation" does not exist.', 18, $this->source);
})()), 'data' => ['sidebar' =>         // line 20
(isset($context['sidebarState']) || array_key_exists('sidebarState', $context) ? $context['sidebarState'] : (function () {
    throw new RuntimeError('Variable "sidebarState" does not exist.', 20, $this->source);
})()), ], ], ((        // line 22
    $context['bodyAttributes']) ?? ([])), true);
        // line 25
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 25, $this->source);
        })()), 'registerAssetBundle', [0 => 'craft\\web\\assets\\cp\\CpAsset'], 'method');
        // line 26
        $context['cpAssetUrl'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 26, $this->source);
        })()), 'getAssetManager', [], 'method'), 'getPublishedUrl', [0 => '@app/web/assets/cp/dist', 1 => true], 'method');
        // line 28
        echo \Craft::$app->getView()->invokeHook('cp.layouts.base', $context);

        // line 30
        echo '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="';
        // line 31
        echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 31, $this->source);
        })()), 'app', []), 'language', []), 'html', null, true);
        echo '">
<head>
    ';
        // line 33
        $this->displayBlock('head', $context, $blocks);
        // line 60
        echo '</head>
<body ';
        // line 61
        echo craft\helpers\Html::renderTagAttributes((isset($context['bodyAttributes']) || array_key_exists('bodyAttributes', $context) ? $context['bodyAttributes'] : (function () {
            throw new RuntimeError('Variable "bodyAttributes" does not exist.', 61, $this->source);
        })()));
        echo '>
    ';
        // line 62
        $this->env->getFunction('beginBody')->getCallable()();
        echo '
    ';
        // line 63
        $this->displayBlock('body', $context, $blocks);
        // line 64
        echo '    ';
        $this->loadTemplate('_layouts/components/notifications', '_layouts/base', 64)->display($context);
        // line 65
        echo '    ';
        $this->displayBlock('foot', $context, $blocks);
        // line 66
        echo '    ';
        $this->env->getFunction('endBody')->getCallable()();
        echo '
</body>
</html>
';
        craft\helpers\Template::endProfile('template', '_layouts/base');
    }

    // line 33
    public function block_head($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'head');
        // line 34
        echo '    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <title>';
        // line 36
        echo twig_escape_filter($this->env, (((isset($context['docTitle']) || array_key_exists('docTitle', $context) ? $context['docTitle'] : (function () {
            throw new RuntimeError('Variable "docTitle" does not exist.', 36, $this->source);
        })()).((($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['docTitle']) || array_key_exists('docTitle', $context) ? $context['docTitle'] : (function () {
            throw new RuntimeError('Variable "docTitle" does not exist.', 36, $this->source);
        })())) && $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 36, $this->source);
        })())))) ? (' - ') : (''))).(isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 36, $this->source);
        })())), 'html', null, true);
        echo '</title>
    ';
        // line 37
        $this->env->getFunction('head')->getCallable()();
        echo '
    <meta name="referrer" content="origin-when-cross-origin">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    ';
        // line 41
        $context['hasCustomIcon'] = false;
        // line 42
        echo '    ';
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 42, $this->source);
        })()), 'app', []), 'config', []), 'general', []), 'cpHeadTags', []));
        foreach ($context['_seq'] as $context['_key'] => $context['tag']) {
            // line 43
            echo '        ';
            echo $this->extensions['craft\web\twig\Extension']->tagFunction(craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 0, [], 'array'), craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 1, [], 'array'));
            echo '
        ';
            // line 44
            if (((craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 0, [], 'array') == 'link') && ((((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 1, [], 'array', false, true), 'rel', [], 'any', true, true) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 1, [], 'array', false, true), 'rel', []) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 1, [], 'array', false, true), 'rel', [])) : (null)) == 'icon'))) {
                // line 45
                echo '            ';
                $context['hasCustomIcon'] = true;
                // line 46
                echo '        ';
            }
            // line 47
            echo '    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['tag'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 48
        echo '    ';
        if (! (isset($context['hasCustomIcon']) || array_key_exists('hasCustomIcon', $context) ? $context['hasCustomIcon'] : (function () {
            throw new RuntimeError('Variable "hasCustomIcon" does not exist.', 48, $this->source);
        })())) {
            // line 49
            echo '        <link rel="icon" href="';
            echo twig_escape_filter($this->env, (isset($context['cpAssetUrl']) || array_key_exists('cpAssetUrl', $context) ? $context['cpAssetUrl'] : (function () {
                throw new RuntimeError('Variable "cpAssetUrl" does not exist.', 49, $this->source);
            })()), 'html', null, true);
            echo '/images/icons/favicon.ico">
        <link rel="icon" type="image/svg+xml" sizes="any" href="';
            // line 50
            echo twig_escape_filter($this->env, (isset($context['cpAssetUrl']) || array_key_exists('cpAssetUrl', $context) ? $context['cpAssetUrl'] : (function () {
                throw new RuntimeError('Variable "cpAssetUrl" does not exist.', 50, $this->source);
            })()), 'html', null, true);
            echo '/images/icons/icon.svg">
        <link rel="apple-touch-icon" sizes="180x180" href="';
            // line 51
            echo twig_escape_filter($this->env, (isset($context['cpAssetUrl']) || array_key_exists('cpAssetUrl', $context) ? $context['cpAssetUrl'] : (function () {
                throw new RuntimeError('Variable "cpAssetUrl" does not exist.', 51, $this->source);
            })()), 'html', null, true);
            echo '/images/icons/apple-touch-icon.png">
        <link rel="mask-icon" href="';
            // line 52
            echo twig_escape_filter($this->env, (isset($context['cpAssetUrl']) || array_key_exists('cpAssetUrl', $context) ? $context['cpAssetUrl'] : (function () {
                throw new RuntimeError('Variable "cpAssetUrl" does not exist.', 52, $this->source);
            })()), 'html', null, true);
            echo '/images/icons/safari-pinned-tab.svg" color="#e5422b">
    ';
        }
        // line 54
        echo '
    <script type="text/javascript">
        // Fix for Firefox autofocus CSS bug
        // See: http://stackoverflow.com/questions/18943276/html-5-autofocus-messes-up-css-loading/18945951#18945951
    </script>
    ';
        craft\helpers\Template::endProfile('block', 'head');
    }

    // line 63
    public function block_body($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'body');
        craft\helpers\Template::endProfile('block', 'body');
    }

    // line 65
    public function block_foot($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'foot');
        craft\helpers\Template::endProfile('block', 'foot');
    }

    public function getTemplateName()
    {
        return '_layouts/base';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [200 => 65,  192 => 63,  182 => 54,  177 => 52,  173 => 51,  169 => 50,  164 => 49,  161 => 48,  155 => 47,  152 => 46,  149 => 45,  147 => 44,  142 => 43,  137 => 42,  135 => 41,  128 => 37,  124 => 36,  120 => 34,  115 => 33,  105 => 66,  102 => 65,  99 => 64,  97 => 63,  93 => 62,  89 => 61,  86 => 60,  84 => 33,  79 => 31,  76 => 30,  73 => 28,  71 => 26,  69 => 25,  67 => 22,  66 => 20,  65 => 18,  64 => 17,  63 => 16,  61 => 15,  59 => 12,  58 => 11,  57 => 10,  56 => 9,  55 => 8,  54 => 7,  51 => 6,  49 => 5,  47 => 4,  45 => 3,  43 => 2,  41 => 1];
    }

    public function getSourceContext()
    {
        return new Source("{% set systemName = craft.app.getSystemName()|t('site') -%}
{% set docTitle = docTitle is defined ? docTitle : title|striptags -%}
{% set orientation = craft.app.locale.getOrientation() -%}
{% set a11yDefaults = craft.app.config.general.accessibilityDefaults %}
{% set requestedSite = craft.cp.requestedSite %}

{% set bodyClass = (bodyClass ?? [])|explodeClass|merge([
    orientation,
    not (currentUser.getPreference('alwaysShowFocusRings') ?? a11yDefaults['alwaysShowFocusRings'] ?? false) ? 'reduce-focus-visibility',
    (currentUser.getPreference('useShapes') ?? a11yDefaults['useShapes'] ?? false) ? 'use-shapes',
    (currentUser.getPreference('underlineLinks') ?? a11yDefaults['underlineLinks'] ?? false) ? 'underline-links',
    requestedSite ? \"site--#{requestedSite.handle}\",
])|filter -%}

{% set sidebarState = craft.app.request.rawCookies.value('Craft-' ~ craft.app.systemUid ~ ':sidebar') ?? 'expanded' %}
{% set bodyAttributes = {
    class: bodyClass,
    dir: orientation,
    data: {
        sidebar: sidebarState
    }
}|merge(bodyAttributes ?? {}, recursive=true) -%}


{% do view.registerAssetBundle('craft\\\\web\\\\assets\\\\cp\\\\CpAsset') -%}
{% set cpAssetUrl = view.getAssetManager().getPublishedUrl('@app/web/assets/cp/dist', true) -%}

{% hook \"cp.layouts.base\" -%}

<!DOCTYPE html>
<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"{{ craft.app.language }}\">
<head>
    {% block head %}
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
    <meta charset=\"utf-8\">
    <title>{{ docTitle ~ (docTitle|length and systemName|length ? ' - ') ~ systemName }}</title>
    {{ head() }}
    <meta name=\"referrer\" content=\"origin-when-cross-origin\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">

    {% set hasCustomIcon = false %}
    {% for tag in craft.app.config.general.cpHeadTags %}
        {{ tag(tag[0], tag[1]) }}
        {% if tag[0] == 'link' and (tag[1].rel ?? null) == 'icon' %}
            {% set hasCustomIcon = true %}
        {% endif %}
    {% endfor %}
    {% if not hasCustomIcon %}
        <link rel=\"icon\" href=\"{{ cpAssetUrl }}/images/icons/favicon.ico\">
        <link rel=\"icon\" type=\"image/svg+xml\" sizes=\"any\" href=\"{{ cpAssetUrl }}/images/icons/icon.svg\">
        <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"{{ cpAssetUrl }}/images/icons/apple-touch-icon.png\">
        <link rel=\"mask-icon\" href=\"{{ cpAssetUrl }}/images/icons/safari-pinned-tab.svg\" color=\"#e5422b\">
    {% endif %}

    <script type=\"text/javascript\">
        // Fix for Firefox autofocus CSS bug
        // See: http://stackoverflow.com/questions/18943276/html-5-autofocus-messes-up-css-loading/18945951#18945951
    </script>
    {% endblock %}
</head>
<body {{ attr(bodyAttributes) }}>
    {{ beginBody() }}
    {% block body %}{% endblock %}
    {% include '_layouts/components/notifications' %}
    {% block foot %}{% endblock %}
    {{ endBody() }}
</body>
</html>
", '_layouts/base', '/Users/brianhanson/Development/craft5/src/templates/_layouts/base.twig');
    }
}
