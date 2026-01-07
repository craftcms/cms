<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__fb93d6df0ad08b7622c6417407fd81f4 */
class __TwigTemplate_c41aff1957bf957917631d03ecc28f4c extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__fb93d6df0ad08b7622c6417407fd81f4');
        // line 1
        yield craft\helpers\Template::attribute($this->env, $this->source, ($context['object'] ?? null), 'firstName', [], 'any', false, false, false, 1);
        craft\helpers\Template::endProfile('template', '__string_template__fb93d6df0ad08b7622c6417407fd81f4');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__fb93d6df0ad08b7622c6417407fd81f4';
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
        return new Source('{{ object.firstName}}', '__string_template__fb93d6df0ad08b7622c6417407fd81f4', '');
    }
}
