<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__862f35f17a7a6a1668b21a7890d7e169 */
class __TwigTemplate_46ce75600c93deadfaadd9ced5293c63 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__862f35f17a7a6a1668b21a7890d7e169');
        // line 1
        yield isset($context['fromName']) || array_key_exists('fromName', $context) ? $context['fromName'] : (function () {
            throw new RuntimeError('Variable "fromName" does not exist.', 1, $this->source);
        })();
        yield ' || ';
        yield isset($context['fromEmail']) || array_key_exists('fromEmail', $context) ? $context['fromEmail'] : (function () {
            throw new RuntimeError('Variable "fromEmail" does not exist.', 1, $this->source);
        })();
        craft\helpers\Template::endProfile('template', '__string_template__862f35f17a7a6a1668b21a7890d7e169');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__862f35f17a7a6a1668b21a7890d7e169';
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
        return new Source('{{fromName}} || {{fromEmail}}', '__string_template__862f35f17a7a6a1668b21a7890d7e169', '');
    }
}
