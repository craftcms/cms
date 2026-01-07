<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__0f24a554b52febe314d4c6179c0dce9b */
class __TwigTemplate_77080058c28cbad8273f4662517b74c8 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__0f24a554b52febe314d4c6179c0dce9b');
        // line 1
        yield craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 1, $this->source);
        })()), 'firstName', [], 'any', false, false, false, 1);
        yield ' | ';
        yield craft\helpers\Template::attribute($this->env, $this->source, (isset($context['currentUser']) || array_key_exists('currentUser', $context) ? $context['currentUser'] : (function () {
            throw new RuntimeError('Variable "currentUser" does not exist.', 1, $this->source);
        })()), 'lastName', [], 'any', false, false, false, 1);
        craft\helpers\Template::endProfile('template', '__string_template__0f24a554b52febe314d4c6179c0dce9b');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__0f24a554b52febe314d4c6179c0dce9b';
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
        return [43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source('{{ currentUser.firstName }} | {{ currentUser.lastName }}', '__string_template__0f24a554b52febe314d4c6179c0dce9b', '');
    }
}
