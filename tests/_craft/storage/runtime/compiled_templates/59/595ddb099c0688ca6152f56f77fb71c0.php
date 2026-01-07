<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__a35b8b834d90b0d7c4c496cb3c771810 */
class __TwigTemplate_e2149c905791389c85dd7c568f2dce54 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__a35b8b834d90b0d7c4c496cb3c771810');
        // line 1
        yield isset($context['fromEmail']) || array_key_exists('fromEmail', $context) ? $context['fromEmail'] : (function () {
            throw new RuntimeError('Variable "fromEmail" does not exist.', 1, $this->source);
        })();
        yield ' || ';
        yield isset($context['fromName']) || array_key_exists('fromName', $context) ? $context['fromName'] : (function () {
            throw new RuntimeError('Variable "fromName" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__a35b8b834d90b0d7c4c496cb3c771810');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__a35b8b834d90b0d7c4c496cb3c771810';
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
        return new Source('{{fromEmail}} || {{fromName}}', '__string_template__a35b8b834d90b0d7c4c496cb3c771810', '');
    }
}
