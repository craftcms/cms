<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__f7cdef0345ea410a24a90c3e627b37c3 */
class __TwigTemplate_2a73f24a79f8aa7e72bf00c9ac560c32 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__f7cdef0345ea410a24a90c3e627b37c3');
        // line 1
        yield 'Hallo ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['user']) || array_key_exists('user', $context) ? $context['user'] : (function () {
            throw new RuntimeError('Variable "user" does not exist.', 1, $this->source);
        })()), 'friendlyName', [], 'any', false, false, false, 1));
        yield ',

Bedankt voor het maken van een account op ';
        // line 3
        yield isset($context['siteName']) || array_key_exists('siteName', $context) ? $context['siteName'] : (function () {
            throw new RuntimeError('Variable "siteName" does not exist.', 3, $this->source);
        })();
        yield '! Klik op de volgende link om je account te activeren:

<';
        // line 5
        yield isset($context['link']) || array_key_exists('link', $context) ? $context['link'] : (function () {
            throw new RuntimeError('Variable "link" does not exist.', 5, $this->source);
        })();
        yield '>

Als je deze e-mail niet verwachtte, kun je hem gewoon negeren.';
        craft\helpers\Template::endProfile('template', '__string_template__f7cdef0345ea410a24a90c3e627b37c3');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__f7cdef0345ea410a24a90c3e627b37c3';
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
        return [54 => 5,  49 => 3,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source('Hallo {{user.friendlyName|e}},

Bedankt voor het maken van een account op {{siteName}}! Klik op de volgende link om je account te activeren:

<{{link}}>

Als je deze e-mail niet verwachtte, kun je hem gewoon negeren.', '__string_template__f7cdef0345ea410a24a90c3e627b37c3', '');
    }
}
