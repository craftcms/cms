<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* _special/email.twig */
class __TwigTemplate_ba63f8d84e258189922bdfd156feb777 extends Template
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
        craft\helpers\Template::beginProfile('template', '_special/email.twig');
        // line 1
        yield '<html lang="';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['language']) || array_key_exists('language', $context) ? $context['language'] : (function () {
            throw new RuntimeError('Variable "language" does not exist.', 1, $this->source);
        })()), 'html', null, true);
        yield '">
<head><meta http-equiv="Content-Type" content="text/html charset=UTF-8" /></head>
<body>
    <div style="max-width: 500px; font-size: 13px; line-height: 18px; font-family: HelveticaNeue, sans-serif;">
        ';
        // line 5
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape((isset($context['body']) || array_key_exists('body', $context) ? $context['body'] : (function () {
            throw new RuntimeError('Variable "body" does not exist.', 5, $this->source);
        })()), 'html', null, true);
        yield '
    </div>
</body>
</html>
';
        craft\helpers\Template::endProfile('template', '_special/email.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_special/email.twig';
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
        return [51 => 5,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source('<html lang="{{ language }}">
<head><meta http-equiv="Content-Type" content="text/html charset=UTF-8" /></head>
<body>
    <div style="max-width: 500px; font-size: 13px; line-height: 18px; font-family: HelveticaNeue, sans-serif;">
        {{ body }}
    </div>
</body>
</html>
', '_special/email.twig', '/tmp/packages/craft5/src/templates/_special/email.twig');
    }
}
