<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__d521d5e4c47799ef6a24d44b3058af2e */
class __TwigTemplate_4b8ce4919349369b565b5e1e10d2f65f extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__d521d5e4c47799ef6a24d44b3058af2e');
        // line 1
        yield 'Hey ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['user']) || array_key_exists('user', $context) ? $context['user'] : (function () {
            throw new RuntimeError('Variable "user" does not exist.', 1, $this->source);
        })()), 'friendlyName', [], 'any', false, false, false, 1));
        yield ',

To reset your ';
        // line 3
        yield isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 3, $this->source);
        })();
        yield ' password, click on this link:

<';
        // line 5
        yield isset($context['link']) || array_key_exists('link', $context) ? $context['link'] : (function () {
            throw new RuntimeError('Variable "link" does not exist.', 5, $this->source);
        })();
        yield '>

If you were not expecting this email, just ignore it.';
        craft\helpers\Template::endProfile('template', '__string_template__d521d5e4c47799ef6a24d44b3058af2e');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__d521d5e4c47799ef6a24d44b3058af2e';
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
        return new Source('Hey {{user.friendlyName|e}},

To reset your {{systemName}} password, click on this link:

<{{link}}>

If you were not expecting this email, just ignore it.', '__string_template__d521d5e4c47799ef6a24d44b3058af2e', '');
    }
}
