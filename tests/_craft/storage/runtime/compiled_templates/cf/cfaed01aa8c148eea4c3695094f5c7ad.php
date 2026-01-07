<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0 */
class __TwigTemplate_56ebf828303e7d4275a0a1a8dbdf1b73 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0');
        // line 1
        yield 'Hey ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['user']) || array_key_exists('user', $context) ? $context['user'] : (function () {
            throw new RuntimeError('Variable "user" does not exist.', 1, $this->source);
        })()), 'friendlyName', [], 'any', false, false, false, 1));
        yield ',

Please verify your new email address by clicking on this link:

<';
        // line 5
        yield isset($context['link']) || array_key_exists('link', $context) ? $context['link'] : (function () {
            throw new RuntimeError('Variable "link" does not exist.', 5, $this->source);
        })();
        yield '>

If you were not expecting this email, just ignore it.';
        craft\helpers\Template::endProfile('template', '__string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0';
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
        return [51 => 5,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source('Hey {{user.friendlyName|e}},

Please verify your new email address by clicking on this link:

<{{link}}>

If you were not expecting this email, just ignore it.', '__string_template__0b8ddc7b78e5b077d28b759f3bf9f6d0', '');
    }
}
