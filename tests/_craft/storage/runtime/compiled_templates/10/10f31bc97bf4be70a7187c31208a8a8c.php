<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/widgets/Feed/body.twig */
class __TwigTemplate_87d27dc75b7300874ea74d173538287e extends Template
{
    private readonly Source $source;

    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        craft\helpers\Template::beginProfile('template', '_components/widgets/Feed/body.twig');
        // line 1
        $macros['links'] = $this->macros['links'] = $this->loadTemplate('_includes/links', '_components/widgets/Feed/body.twig', 1)->unwrap();
        // line 2
        yield '
<ol class="widget__list" role="list" dir="';
        // line 3
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['feed']) || array_key_exists('feed', $context) ? $context['feed'] : (function () {
            throw new RuntimeError('Variable "feed" does not exist.', 3, $this->source);
        })()), 'direction', [], 'any', false, false, false, 3), 'html', null, true);
        yield '">
    ';
        // line 4
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['feed']) || array_key_exists('feed', $context) ? $context['feed'] : (function () {
            throw new RuntimeError('Variable "feed" does not exist.', 4, $this->source);
        })()), 'items', [], 'any', false, false, false, 4));
        foreach ($context['_seq'] as $context['_key'] => $context['item']) {
            // line 5
            yield '        <li class="widget__list-item">
            ';
            // line 6
            if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'permalink', [], 'any', true, true, false, 6) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'permalink', [], 'any', false, false, false, 6) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'permalink', [], 'any', false, false, false, 6)) : (false))) {
                // line 7
                yield '                ';
                yield CoreExtension::callMacro($macros['links'], 'macro_externalLink', [['link' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 8
                    $context['item'], 'permalink', [], 'any', false, false, false, 8), 'text' => craft\helpers\Template::attribute($this->env, $this->source,                 // line 9
                        $context['item'], 'title', [], 'any', false, false, false, 9)]], 7, $context, $this->getSourceContext());
                // line 10
                yield '
                ';
                // line 11
                if ((((craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'date', [], 'any', true, true, false, 11) && ! (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'date', [], 'any', false, false, false, 11) === null))) ? (craft\helpers\Template::attribute($this->env, $this->source, $context['item'], 'date', [], 'any', false, false, false, 11)) : (false))) {
                    // line 12
                    yield '                    ';
                    yield $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'light nowrap', 'text' => $this->extensions['craft\web\twig\Extension']->timestampFilter(craft\helpers\Template::attribute($this->env, $this->source,                     // line 14
                        $context['item'], 'date', [], 'any', false, false, false, 14), 'short')]);
                    // line 15
                    yield '
                ';
                }
                // line 17
                yield '            ';
            } else {
                // line 18
                yield '                &nbsp;
            ';
            }
            // line 20
            yield '        </li>
    ';
        }
        unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
        // line 22
        yield '</ol>
';
        craft\helpers\Template::endProfile('template', '_components/widgets/Feed/body.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/widgets/Feed/body.twig';
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
        return [93 => 22,  86 => 20,  82 => 18,  79 => 17,  75 => 15,  73 => 14,  71 => 12,  69 => 11,  66 => 10,  64 => 9,  63 => 8,  61 => 7,  59 => 6,  56 => 5,  52 => 4,  48 => 3,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% import \"_includes/links\" as links %}

<ol class=\"widget__list\" role=\"list\" dir=\"{{ feed.direction }}\">
    {% for item in feed.items %}
        <li class=\"widget__list-item\">
            {% if item.permalink ?? false %}
                {{ links.externalLink({
                    link: item.permalink,
                    text: item.title,
                }) }}
                {% if item.date ?? false %}
                    {{ tag('span', {
                        class: 'light nowrap',
                        text: item.date|timestamp('short'),
                    }) }}
                {% endif %}
            {% else %}
                &nbsp;
            {% endif %}
        </li>
    {% endfor %}
</ol>
", '_components/widgets/Feed/body.twig', '/tmp/packages/craft5/src/templates/_components/widgets/Feed/body.twig');
    }
}
