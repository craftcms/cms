<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _layouts/base */
class __TwigTemplate_25bc26930afd81594cdb810496da86b7 extends Template
{
    private readonly Source $source;

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

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '_layouts/base');
        // line 1
        $context['systemName'] = $this->extensions['craft\web\twig\Extension']->translateFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'app', [], 'any', false, false, false, 1), 'getSystemName', [], 'method', false, false, false, 1), 'site');
        // line 2
        $context['docTitle'] = ((array_key_exists('docTitle', $context)) ? ((isset($context['docTitle']) || array_key_exists('docTitle', $context) ? $context['docTitle'] : (function () {
            throw new RuntimeError('Variable "docTitle" does not exist.', 2, $this->source);
        })())) : (Twig\Extension\CoreExtension::striptags((isset($context['title']) || array_key_exists('title', $context) ? $context['title'] : (function () {
            throw new RuntimeError('Variable "title" does not exist.', 2, $this->source);
        })()))));
        // line 3
        $context['orientation'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 3, $this->source);
        })()), 'app', [], 'any', false, false, false, 3), 'locale', [], 'any', false, false, false, 3), 'getOrientation', [], 'method', false, false, false, 3);
        // line 4
        $context['a11yDefaults'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 4, $this->source);
        })()), 'app', [], 'any', false, false, false, 4), 'config', [], 'any', false, false, false, 4), 'general', [], 'any', false, false, false, 4), 'accessibilityDefaults', [], 'any', false, false, false, 4);
        // line 5
        $context['requestedSite'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 5, $this->source);
        })()), 'cp', [], 'any', false, false, false, 5), 'requestedSite', [], 'any', false, false, false, 5);
        // line 6
        yield '
';
        // line 7
        $context['bodyClass'] = $this->extensions['craft\web\twig\Extension']->filterFilter($this->env, $this->extensions['craft\web\twig\Extension']->mergeFilter(craft\helpers\Html::explodeClass((($context['bodyClass']) ?? ([]))), [        // line 8
            (isset($context['orientation']) || array_key_exists('orientation', $context) ? $context['orientation'] : (function () {
                throw new RuntimeError('Variable "orientation" does not exist.', 8, $this->source);
            })()), ((! (((craft\helpers\Template::attribute($this->env, $this->source,         // line 9
                ($context['currentUser'] ?? null), 'getPreference', ['alwaysShowFocusRings'], 'method', true, true, false, 9) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', ['alwaysShowFocusRings'], 'method', false, false, false, 9) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', ['alwaysShowFocusRings'], 'method', false, false, false, 9)) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'alwaysShowFocusRings', [], 'array', true, true, false, 9) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'alwaysShowFocusRings', [], 'array', false, false, false, 9) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'alwaysShowFocusRings', [], 'array', false, false, false, 9)) : (false))))) ? ('reduce-focus-visibility') : ('')), (((((craft\helpers\Template::attribute($this->env, $this->source,         // line 10
                    ($context['currentUser'] ?? null), 'getPreference', ['useShapes'], 'method', true, true, false, 10) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', ['useShapes'], 'method', false, false, false, 10) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', ['useShapes'], 'method', false, false, false, 10)) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'useShapes', [], 'array', true, true, false, 10) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'useShapes', [], 'array', false, false, false, 10) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'useShapes', [], 'array', false, false, false, 10)) : (false))))) ? ('use-shapes') : ('')), (((((craft\helpers\Template::attribute($this->env, $this->source,         // line 11
                        ($context['currentUser'] ?? null), 'getPreference', ['underlineLinks'], 'method', true, true, false, 11) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', ['underlineLinks'], 'method', false, false, false, 11) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['currentUser'] ?? null), 'getPreference', ['underlineLinks'], 'method', false, false, false, 11)) : ((((craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'underlineLinks', [], 'array', true, true, false, 11) && ! (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'underlineLinks', [], 'array', false, false, false, 11) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, ($context['a11yDefaults'] ?? null), 'underlineLinks', [], 'array', false, false, false, 11)) : (false))))) ? ('underline-links') : ('')), ((        // line 12
                            (isset($context['requestedSite']) || array_key_exists('requestedSite', $context) ? $context['requestedSite'] : (function () {
                                throw new RuntimeError('Variable "requestedSite" does not exist.', 12, $this->source);
                            })())) ? (('site--'.craft\helpers\Template::attribute($this->env, $this->source, (isset($context['requestedSite']) || array_key_exists('requestedSite', $context) ? $context['requestedSite'] : (function () {
                                throw new RuntimeError('Variable "requestedSite" does not exist.', 12, $this->source);
                            })()), 'handle', [], 'any', false, false, false, 12))) : (''))]));
        // line 15
        $context['sidebarState'] = (((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true, false, 15), 'request', [], 'any', false, true, false, 15), 'rawCookies', [], 'any', false, true, false, 15), 'value', [(('Craft-'.craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
        })()), 'app', [], 'any', false, false, false, 15), 'systemUid', [], 'any', false, false, false, 15)).':sidebar')], 'method', true, true, false, 15) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true, false, 15), 'request', [], 'any', false, true, false, 15), 'rawCookies', [], 'any', false, true, false, 15), 'value', [(('Craft-'.craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
        })()), 'app', [], 'any', false, false, false, 15), 'systemUid', [], 'any', false, false, false, 15)).':sidebar')], 'method', false, false, false, 15) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, ($context['craft'] ?? null), 'app', [], 'any', false, true, false, 15), 'request', [], 'any', false, true, false, 15), 'rawCookies', [], 'any', false, true, false, 15), 'value', [(('Craft-'.craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 15, $this->source);
        })()), 'app', [], 'any', false, false, false, 15), 'systemUid', [], 'any', false, false, false, 15)).':sidebar')], 'method', false, false, false, 15)) : ('expanded'));
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
            })())]], ((        // line 22
                $context['bodyAttributes']) ?? ([])), true);
        // line 25
        craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 25, $this->source);
        })()), 'registerAssetBundle', ['craft\\web\\assets\\cp\\CpAsset'], 'method', false, false, false, 25);
        // line 26
        $context['cpAssetUrl'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['view']) || array_key_exists('view', $context) ? $context['view'] : (function () {
            throw new RuntimeError('Variable "view" does not exist.', 26, $this->source);
        })()), 'getAssetManager', [], 'method', false, false, false, 26), 'getPublishedUrl', ['@app/web/assets/cp/dist', true], 'method', false, false, false, 26);
        // line 28
        echo \Craft::$app->getView()->invokeHook('cp.layouts.base', $context);

        // line 30
        yield '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="';
        // line 31
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 31, $this->source);
        })()), 'app', [], 'any', false, false, false, 31), 'language', [], 'any', false, false, false, 31), 'html', null, true);
        yield '">
<head>
    ';
        // line 33
        yield from $this->unwrap()->yieldBlock('head', $context, $blocks);
        // line 60
        yield '</head>
<body ';
        // line 61
        yield craft\helpers\Html::renderTagAttributes((isset($context['bodyAttributes']) || array_key_exists('bodyAttributes', $context) ? $context['bodyAttributes'] : (function () {
            throw new RuntimeError('Variable "bodyAttributes" does not exist.', 61, $this->source);
        })()));
        yield '>
    ';
        // line 62
        $this->env->getFunction('beginBody')->getCallable()();
        yield '
    ';
        // line 63
        yield from $this->loadTemplate('_layouts/components/global-live-region', '_layouts/base', 63)->unwrap()->yield($context);
        // line 64
        yield '    ';
        yield from $this->unwrap()->yieldBlock('body', $context, $blocks);
        // line 65
        yield '    ';
        yield from $this->loadTemplate('_layouts/components/notifications', '_layouts/base', 65)->unwrap()->yield($context);
        // line 66
        yield '    ';
        yield from $this->unwrap()->yieldBlock('foot', $context, $blocks);
        // line 67
        yield '    ';
        $this->env->getFunction('endBody')->getCallable()();
        yield '
</body>
</html>
';
        craft\helpers\Template::endProfile('template', '_layouts/base');
        yield from [];
    }

    // line 33
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_head(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'head');
        // line 34
        yield '    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <title>';
        // line 36
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((((isset($context['docTitle']) || array_key_exists('docTitle', $context) ? $context['docTitle'] : (function () {
            throw new RuntimeError('Variable "docTitle" does not exist.', 36, $this->source);
        })()).((($this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['docTitle']) || array_key_exists('docTitle', $context) ? $context['docTitle'] : (function () {
            throw new RuntimeError('Variable "docTitle" does not exist.', 36, $this->source);
        })())) && $this->extensions['craft\web\twig\Extension']->lengthFilter($this->env, (isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 36, $this->source);
        })())))) ? (' - ') : (''))).(isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 36, $this->source);
        })())), 'html', null, true);
        yield '</title>
    ';
        // line 37
        $this->env->getFunction('head')->getCallable()();
        yield '
    <meta name="referrer" content="origin-when-cross-origin">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    ';
        // line 41
        $context['hasCustomIcon'] = false;
        // line 42
        yield '    ';
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 42, $this->source);
        })()), 'app', [], 'any', false, false, false, 42), 'config', [], 'any', false, false, false, 42), 'general', [], 'any', false, false, false, 42), 'cpHeadTags', [], 'any', false, false, false, 42));
        foreach ($context['_seq'] as $context['_key'] => $context['tag']) {
            // line 43
            yield '        ';
            yield $this->extensions['craft\web\twig\Extension']->tagFunction(craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 0, [], 'array', false, false, false, 43), craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 1, [], 'array', false, false, false, 43));
            yield '
        ';
            // line 44
            if (((craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 0, [], 'array', false, false, false, 44) == 'link') && ((((craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 1, [], 'array', false, true, false, 44), 'rel', [], 'any', true, true, false, 44) && ! (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 1, [], 'array', false, true, false, 44), 'rel', [], 'any', false, false, false, 44) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, $context['tag'], 1, [], 'array', false, true, false, 44), 'rel', [], 'any', false, false, false, 44)) : (null)) == 'icon'))) {
                // line 45
                yield '            ';
                $context['hasCustomIcon'] = true;
                // line 46
                yield '        ';
            }
            // line 47
            yield '    ';
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['tag'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 48
        yield '    ';
        if (! (isset($context['hasCustomIcon']) || array_key_exists('hasCustomIcon', $context) ? $context['hasCustomIcon'] : (function () {
            throw new RuntimeError('Variable "hasCustomIcon" does not exist.', 48, $this->source);
        })())) {
            // line 49
            yield '        <link rel="icon" href="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['cpAssetUrl']) || array_key_exists('cpAssetUrl', $context) ? $context['cpAssetUrl'] : (function () {
                throw new RuntimeError('Variable "cpAssetUrl" does not exist.', 49, $this->source);
            })()), 'html', null, true);
            yield '/images/icons/favicon.ico">
        <link rel="icon" type="image/svg+xml" sizes="any" href="';
            // line 50
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['cpAssetUrl']) || array_key_exists('cpAssetUrl', $context) ? $context['cpAssetUrl'] : (function () {
                throw new RuntimeError('Variable "cpAssetUrl" does not exist.', 50, $this->source);
            })()), 'html', null, true);
            yield '/images/icons/icon.svg">
        <link rel="apple-touch-icon" sizes="180x180" href="';
            // line 51
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['cpAssetUrl']) || array_key_exists('cpAssetUrl', $context) ? $context['cpAssetUrl'] : (function () {
                throw new RuntimeError('Variable "cpAssetUrl" does not exist.', 51, $this->source);
            })()), 'html', null, true);
            yield '/images/icons/apple-touch-icon.png">
        <link rel="mask-icon" href="';
            // line 52
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['cpAssetUrl']) || array_key_exists('cpAssetUrl', $context) ? $context['cpAssetUrl'] : (function () {
                throw new RuntimeError('Variable "cpAssetUrl" does not exist.', 52, $this->source);
            })()), 'html', null, true);
            yield '/images/icons/safari-pinned-tab.svg" color="#e5422b">
    ';
        }
        // line 54
        yield '
    <script type="text/javascript">
        // Fix for Firefox autofocus CSS bug
        // See: http://stackoverflow.com/questions/18943276/html-5-autofocus-messes-up-css-loading/18945951#18945951
    </script>
    ';
        craft\helpers\Template::endProfile('block', 'head');
        yield from [];
    }

    // line 64
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_body(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'body');
        craft\helpers\Template::endProfile('block', 'body');
        yield from [];
    }

    // line 66
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_foot(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'foot');
        craft\helpers\Template::endProfile('block', 'foot');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_layouts/base';
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return [217 => 66,  205 => 64,  194 => 54,  189 => 52,  185 => 51,  181 => 50,  176 => 49,  173 => 48,  167 => 47,  164 => 46,  161 => 45,  159 => 44,  154 => 43,  149 => 42,  147 => 41,  140 => 37,  136 => 36,  132 => 34,  124 => 33,  113 => 67,  110 => 66,  107 => 65,  104 => 64,  102 => 63,  98 => 62,  94 => 61,  91 => 60,  89 => 33,  84 => 31,  81 => 30,  78 => 28,  76 => 26,  74 => 25,  72 => 22,  71 => 20,  70 => 18,  69 => 17,  68 => 16,  66 => 15,  64 => 12,  63 => 11,  62 => 10,  61 => 9,  60 => 8,  59 => 7,  56 => 6,  54 => 5,  52 => 4,  50 => 3,  48 => 2,  46 => 1];
    }

    public function getSourceContext(): Source
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
    {% include '_layouts/components/global-live-region' %}
    {% block body %}{% endblock %}
    {% include '_layouts/components/notifications' %}
    {% block foot %}{% endblock %}
    {{ endBody() }}
</body>
</html>
", '_layouts/base', '/tmp/packages/craft5/src/templates/_layouts/base.twig');
    }
}
