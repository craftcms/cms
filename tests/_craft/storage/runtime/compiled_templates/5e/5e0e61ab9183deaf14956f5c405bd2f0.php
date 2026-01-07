<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__b964bbcf6b20d4fd55080283dd4ceb10 */
class __TwigTemplate_7e238066ca64bbc347fdd2a9d2aa6710 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b964bbcf6b20d4fd55080283dd4ceb10');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->timeFilter($this->env, (isset($context['d']) || array_key_exists('d', $context) ? $context['d'] : (function () {
            throw new RuntimeError('Variable "d" does not exist.', 1, $this->source);
        })()), 'h:i:s');
        craft\helpers\Template::endProfile('template', '__string_template__b964bbcf6b20d4fd55080283dd4ceb10');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__b964bbcf6b20d4fd55080283dd4ceb10';
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
        return new Source('{{ d|time("h:i:s") }}', '__string_template__b964bbcf6b20d4fd55080283dd4ceb10', '');
    }
}
