<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;

/* _components/utilities/SystemMessages/index.twig */
class __TwigTemplate_0efe8cb35a094949e8ebb06c5bc55d85 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/SystemMessages/index.twig');
        // line 1
        yield '<div id="messages">
    ';
        // line 2
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable((isset($context['messages']) || array_key_exists('messages', $context) ? $context['messages'] : (function () {
            throw new RuntimeError('Variable "messages" does not exist.', 2, $this->source);
        })()));
        foreach ($context['_seq'] as $context['_key'] => $context['message']) {
            // line 3
            yield '        <h2>';
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['message'], 'heading', [], 'any', false, false, false, 3), 'html', null, true);
            yield '</h2>
        <div class="pane message" data-key="';
            // line 4
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['message'], 'key', [], 'any', false, false, false, 4), 'html', null, true);
            yield '">
            <div class="subject">';
            // line 5
            yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['message'], 'subject', [], 'any', false, false, false, 5), 'html', null, true);
            yield '</div>
            <div class="body">';
            // line 6
            yield Twig\Extension\CoreExtension::nl2br($this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, $context['message'], 'body', [], 'any', false, false, false, 6), 'html', null, true));
            yield '</div>
        </div>
    ';
        }
        unset($context['_seq'], $context['_key'], $context['message'], $context['_parent']);
        // line 9
        yield '</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/SystemMessages/index.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/SystemMessages/index.twig';
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
        return [72 => 9,  63 => 6,  59 => 5,  55 => 4,  50 => 3,  46 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
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
', '_components/utilities/SystemMessages/index.twig', '/tmp/packages/craft5/src/templates/_components/utilities/SystemMessages/index.twig');
    }
}
