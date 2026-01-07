<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__adec945b087993e586e200231495e171 */
class __TwigTemplate_6e33e06a7cd509b888b103910ee33c85 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__adec945b087993e586e200231495e171');
        // line 1
        yield craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'app', [], 'any', false, false, false, 1), 'user', [], 'any', false, false, false, 1), 'getIdentity', [], 'method', false, false, false, 1), 'firstName', [], 'any', false, false, false, 1);
        craft\helpers\Template::endProfile('template', '__string_template__adec945b087993e586e200231495e171');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__adec945b087993e586e200231495e171';
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
        return new Source('{{ craft.app.user.getIdentity().firstName }}', '__string_template__adec945b087993e586e200231495e171', '');
    }
}
