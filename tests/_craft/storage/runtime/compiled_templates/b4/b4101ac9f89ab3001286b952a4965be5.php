<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__d42d27c04c8ec5285d52cb2f0d459f4c */
class __TwigTemplate_c095a6e4a8083d2d923faace49e0d49d extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__d42d27c04c8ec5285d52cb2f0d459f4c');
        // line 1
        yield Twig\Extension\CoreExtension::join(Twig\Extension\CoreExtension::keys($this->extensions['craft\web\twig\Extension']->groupFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'users', [], 'method', false, false, false, 1), 'id', [1], 'method', false, false, false, 1), 'all', [], 'method', false, false, false, 1), 'username')), ',');
        craft\helpers\Template::endProfile('template', '__string_template__d42d27c04c8ec5285d52cb2f0d459f4c');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__d42d27c04c8ec5285d52cb2f0d459f4c';
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
        return new Source('{{ craft.users().id(1).all()|group("username")|keys|join(",") }}', '__string_template__d42d27c04c8ec5285d52cb2f0d459f4c', '');
    }
}
