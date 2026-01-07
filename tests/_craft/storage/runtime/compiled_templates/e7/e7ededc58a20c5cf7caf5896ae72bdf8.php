<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__d564fb469ef49d27edba39bd2b086dc3 */
class __TwigTemplate_a894a98e9e0349917803f0c37711cc3b extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__d564fb469ef49d27edba39bd2b086dc3');
        // line 1
        yield craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'app', [], 'any', false, false, false, 1), 'request', [], 'any', false, false, false, 1), 'getRawBody', [], 'method', false, false, false, 1);
        craft\helpers\Template::endProfile('template', '__string_template__d564fb469ef49d27edba39bd2b086dc3');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__d564fb469ef49d27edba39bd2b086dc3';
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
        return new Source('{{ craft.app.request.getRawBody() }}', '__string_template__d564fb469ef49d27edba39bd2b086dc3', '');
    }
}
