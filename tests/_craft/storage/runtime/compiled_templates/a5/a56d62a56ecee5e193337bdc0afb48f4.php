<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* users/_index.twig */
class __TwigTemplate_5fb13822e1c11271df773ebe566b2825 extends Template
{
    private readonly Source $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'actionButton' => $this->block_actionButton(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 5
        return '_layouts/elementindex';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', 'users/_index.twig');
        // line 1
        if (((isset($context['CraftEdition']) || array_key_exists('CraftEdition', $context) ? $context['CraftEdition'] : (function () {
            throw new RuntimeError('Variable "CraftEdition" does not exist.', 1, $this->source);
        })()) == (isset($context['CraftSolo']) || array_key_exists('CraftSolo', $context) ? $context['CraftSolo'] : (function () {
            throw new RuntimeError('Variable "CraftSolo" does not exist.', 1, $this->source);
        })()))) {
            // line 2
            throw new yii\web\NotFoundHttpException;
        }
        // line 6
        $context['elementType'] = 'craft\\elements\\User';
        // line 8
        $context['canHaveDrafts'] = craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 8, $this->source);
        })()), 'users', [], 'method', false, false, false, 8), 'drafts', [], 'method', false, false, false, 8), 'draftOf', [false], 'method', false, false, false, 8), 'savedDraftsOnly', [], 'method', false, false, false, 8), 'exists', [], 'method', false, false, false, 8);
        // line 18
        if ((($context['source']) ?? (false))) {
            // line 19
            ob_start();
            // line 20
            yield '    window.defaultSourceSlug = "';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['source']) || array_key_exists('source', $context) ? $context['source'] : (function () {
                throw new RuntimeError('Variable "source" does not exist.', 20, $this->source);
            })()), 'js'), 'html', null, true);
            yield '";
    ';
            craft\helpers\Template::js(ob_get_clean(), ['position' => 3]);
        }
        // line 5
        $this->parent = $this->loadTemplate('_layouts/elementindex', 'users/_index.twig', 5);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', 'users/_index.twig');
    }

    // line 10
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_actionButton(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'actionButton');
        // line 11
        yield '    ';
        if (craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 11, $this->source);
        })()), 'canRegisterUsers', [], 'method', false, false, false, 11)) {
            // line 12
            yield '        <a class="btn submit add icon" href="';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\UrlHelper::url('users/new'), 'html', null, true);
            yield '">
            ';
            // line 13
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['buttonLabel']) || array_key_exists('buttonLabel', $context) ? $context['buttonLabel'] : (function () {
                throw new RuntimeError('Variable "buttonLabel" does not exist.', 13, $this->source);
            })()), 'html', null, true);
            yield '
        </a>
    ';
        }
        craft\helpers\Template::endProfile('block', 'actionButton');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return 'users/_index.twig';
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
        return [90 => 13,  85 => 12,  82 => 11,  74 => 10,  68 => 5,  61 => 20,  59 => 19,  57 => 18,  55 => 8,  53 => 6,  50 => 2,  48 => 1,  40 => 5];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% if CraftEdition == CraftSolo %}
    {% exit 404 %}
{% endif %}

{% extends \"_layouts/elementindex\" %}
{% set elementType = 'craft\\\\elements\\\\User' %}

{% set canHaveDrafts = craft.users().drafts().draftOf(false).savedDraftsOnly().exists() %}

{% block actionButton %}
    {% if currentUser.canRegisterUsers() %}
        <a class=\"btn submit add icon\" href=\"{{ url('users/new') }}\">
            {{ buttonLabel }}
        </a>
    {% endif %}
{% endblock %}

{% if source ?? false %}
    {% js %}
    window.defaultSourceSlug = \"{{ source|e('js') }}\";
    {% endjs %}
{% endif %}
", 'users/_index.twig', '/tmp/packages/craft5/src/templates/users/_index.twig');
    }
}
