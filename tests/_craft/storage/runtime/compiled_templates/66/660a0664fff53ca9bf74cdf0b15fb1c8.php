<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__9ad34281a63eb0e7d547eb6f25feda81 */
class __TwigTemplate_5938a380e3a2130f9dd388c592f235f9 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__9ad34281a63eb0e7d547eb6f25feda81');
        // line 1
        yield $this->extensions['craft\web\twig\Extension']->replaceFilter('https://foo.com/bar/baz/', '/(http(s?):)?\\/\\/foo\\.com\\/bar\\/baz\\//', 'qux');
        craft\helpers\Template::endProfile('template', '__string_template__9ad34281a63eb0e7d547eb6f25feda81');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__9ad34281a63eb0e7d547eb6f25feda81';
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
        return new Source('{{ "https://foo.com/bar/baz/"|replace("/(http(s?):)?\\\\/\\\\/foo\\\\.com\\\\/bar\\\\/baz\\\\//", "qux") }}', '__string_template__9ad34281a63eb0e7d547eb6f25feda81', '');
    }
}
