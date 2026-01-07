<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _includes/forms/errorList.twig */
class __TwigTemplate_6427a420129b3c5a021e13a10bc446a7 extends Template
{
    private readonly Source $source;

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
        craft\helpers\Template::beginProfile('template', '_includes/forms/errorList.twig');
        // line 1
        if ((isset($context['errors']) || array_key_exists('errors', $context) ? $context['errors'] : (function () {
            throw new RuntimeError('Variable "errors" does not exist.', 1, $this->source);
        })())) {
            // line 2
            yield '    ';
            ob_start();
            // line 6
            yield '        ';
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable((isset($context['errors']) || array_key_exists('errors', $context) ? $context['errors'] : (function () {
                throw new RuntimeError('Variable "errors" does not exist.', 6, $this->source);
            })()));
            foreach ($context['_seq'] as $context['_key'] => $context['error']) {
                // line 7
                yield '            <li>
                ';
                // line 8
                yield $this->extensions['craft\web\twig\Extension']->tagFunction('span', ['class' => 'visually-hidden', 'text' => $this->extensions['craft\web\twig\Extension']->translateFilter('Error:', 'app')]);
                // line 11
                yield '
                ';
                // line 12
                yield $this->extensions['craft\web\twig\Extension']->markdownFilter($context['error'], null, true, true);
                yield '
            </li>
        ';
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['error'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 15
            yield '    ';
            echo craft\helpers\Html::tag('ul', ob_get_clean(), ['id' => ((            // line 3
                $context['id']) ?? (false)), 'class' => 'errors']);
        }
        craft\helpers\Template::endProfile('template', '_includes/forms/errorList.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_includes/forms/errorList.twig';
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
        return [72 => 3,  70 => 15,  61 => 12,  58 => 11,  56 => 8,  53 => 7,  48 => 6,  45 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{%- if errors %}
    {% tag 'ul' with {
        id: id ?? false,
        class: 'errors',
    } %}
        {% for error in errors %}
            <li>
                {{ tag('span', {
                    class: 'visually-hidden',
                    text: 'Error:'|t('app'),
                }) }}
                {{ error|md(inlineOnly=true, encode=true)|raw }}
            </li>
        {% endfor %}
    {% endtag %}
{%- endif %}
", '_includes/forms/errorList.twig', '/tmp/packages/craft5/src/templates/_includes/forms/errorList.twig');
    }
}
