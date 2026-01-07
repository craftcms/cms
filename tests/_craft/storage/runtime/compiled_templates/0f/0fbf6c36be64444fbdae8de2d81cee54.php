<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _components/utilities/SystemMessages/index.twig */
class __TwigTemplate_494c6f925583add4e9d0e1235ce98c1c extends Template
{
    private $source;

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '_components/utilities/SystemMessages/index.twig');
        // line 1
        echo '<div id="messages">
    ';
        // line 2
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable((isset($context['messages']) || array_key_exists('messages', $context) ? $context['messages'] : (function () {
            throw new RuntimeError('Variable "messages" does not exist.', 2, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['message']) {
            // line 3
            echo '        <h2>';
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['message'], 'heading', []), 'html', null, true);
            echo '</h2>
        <div class="pane message" data-key="';
            // line 4
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['message'], 'key', []), 'html', null, true);
            echo '">
            <div class="subject">';
            // line 5
            echo twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['message'], 'subject', []), 'html', null, true);
            echo '</div>
            <div class="body">';
            // line 6
            echo twig_nl2br(twig_escape_filter($this->env, craft\helpers\Template::attribute($this->env, $this->source, $context['message'], 'body', []), 'html', null, true));
            echo '</div>
        </div>
    ';
        }
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['message'], $context['_parent'], $context['loop']);
        // line 9
        echo '</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/SystemMessages/index.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/SystemMessages/index.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [67 => 9,  58 => 6,  54 => 5,  50 => 4,  45 => 3,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source('<div id="messages">
    {% for message in messages %}
        <h2>{{ message.heading }}</h2>
        <div class="pane message" data-key="{{ message.key }}">
            <div class="subject">{{ message.subject }}</div>
            <div class="body">{{ message.body|nl2br }}</div>
        </div>
    {% endfor %}
</div>
', '_components/utilities/SystemMessages/index.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/SystemMessages/index.twig');
    }
}
