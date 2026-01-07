<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__1cc9ba49df6d73e258a863804231e4ca */
class __TwigTemplate_5a7228749ba4f78efda08c81cce3a0ec extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__1cc9ba49df6d73e258a863804231e4ca');
        // line 1
        yield Twig\Extension\CoreExtension::join(Twig\Extension\CoreExtension::keys($this->extensions['craft\web\twig\Extension']->groupFilter(craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, craft\helpers\Template::attribute($this->env, $this->source, (isset($context['craft']) || array_key_exists('craft', $context) ? $context['craft'] : (function () {
            throw new RuntimeError('Variable "craft" does not exist.', 1, $this->source);
        })()), 'users', [], 'method', false, false, false, 1), 'id', [1], 'method', false, false, false, 1), 'all', [], 'method', false, false, false, 1), function ($__u__) use ($context) {
            $context['u'] = $__u__;

            return craft\helpers\Template::attribute($this->env, $this->source, (isset($context['u']) || array_key_exists('u', $context) ? $context['u'] : (function () {
                throw new RuntimeError('Variable "u" does not exist.', 1, $this->source);
            })()), 'username', [], 'any', false, false, false, 1);
        })), ',');
        craft\helpers\Template::endProfile('template', '__string_template__1cc9ba49df6d73e258a863804231e4ca');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__1cc9ba49df6d73e258a863804231e4ca';
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
        return new Source('{{ craft.users().id(1).all()|group(u => u.username)|keys|join(",") }}', '__string_template__1cc9ba49df6d73e258a863804231e4ca', '');
    }
}
