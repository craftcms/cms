<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__6afe6292013668c306397ba7a2132f57 */
class __TwigTemplate_1a5a44cf7b05ae4b51765074f16e335c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__6afe6292013668c306397ba7a2132f57');
        // line 1
        yield ($this->env->getTest('instance of')->getCallable()((isset($context['foo']) || array_key_exists('foo', $context) ? $context['foo'] : (function () {
            throw new RuntimeError('Variable "foo" does not exist.', 1, $this->source);
        })()), (isset($context['class']) || array_key_exists('class', $context) ? $context['class'] : (function () {
            throw new RuntimeError('Variable "class" does not exist.', 1, $this->source);
        })()))) ? ('yes') : ('no');
        craft\helpers\Template::endProfile('template', '__string_template__6afe6292013668c306397ba7a2132f57');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__6afe6292013668c306397ba7a2132f57';
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
        return new Source('{{ foo is instance of(class) ? "yes" : "no" }}', '__string_template__6afe6292013668c306397ba7a2132f57', '');
    }
}
