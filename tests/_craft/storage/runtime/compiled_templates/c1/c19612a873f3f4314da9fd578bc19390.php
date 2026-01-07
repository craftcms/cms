<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__587b4fd843aee497bf5f6f229f603ed1 */
class __TwigTemplate_88289f137c369938bd513322393f2714 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__587b4fd843aee497bf5f6f229f603ed1');
        // line 1
        yield isset($context['arg1']) || array_key_exists('arg1', $context) ? $context['arg1'] : (function () {
            throw new RuntimeError('Variable "arg1" does not exist.', 1, $this->source);
        })();
        yield '-';
        yield isset($context['arg2']) || array_key_exists('arg2', $context) ? $context['arg2'] : (function () {
            throw new RuntimeError('Variable "arg2" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__587b4fd843aee497bf5f6f229f603ed1');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__587b4fd843aee497bf5f6f229f603ed1';
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
        return new Source('{{ arg1 }}-{{ arg2 }}', '__string_template__587b4fd843aee497bf5f6f229f603ed1', '');
    }
}
