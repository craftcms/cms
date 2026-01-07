<?php

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Source;
use Twig\Template;

/* __string_template__4124ceded1f394422fd5dc2e1c7fbc87 */
class __TwigTemplate_8a6106c3b93d7fb8f114ecfd53e29ae0 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__4124ceded1f394422fd5dc2e1c7fbc87');
        // line 1
        yield 'Hey ';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape(craft\helpers\Template::attribute($this->env, $this->source, (isset($context['user']) || array_key_exists('user', $context) ? $context['user'] : (function () {
            throw new RuntimeError('Variable "user" does not exist.', 1, $this->source);
        })()), 'friendlyName', [], 'any', false, false, false, 1));
        yield ',

Thanks for creating an account with ';
        // line 3
        yield isset($context['systemName']) || array_key_exists('systemName', $context) ? $context['systemName'] : (function () {
            throw new RuntimeError('Variable "systemName" does not exist.', 3, $this->source);
        })();
        yield '! To activate your account, click the following link:

<';
        // line 5
        yield isset($context['link']) || array_key_exists('link', $context) ? $context['link'] : (function () {
            throw new RuntimeError('Variable "link" does not exist.', 5, $this->source);
        })();
        yield '>

If you were not expecting this email, just ignore it.';
        craft\helpers\Template::endProfile('template', '__string_template__4124ceded1f394422fd5dc2e1c7fbc87');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__4124ceded1f394422fd5dc2e1c7fbc87';
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
        return [54 => 5,  49 => 3,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source('Hey {{user.friendlyName|e}},

Thanks for creating an account with {{systemName}}! To activate your account, click the following link:

<{{link}}>

If you were not expecting this email, just ignore it.', '__string_template__4124ceded1f394422fd5dc2e1c7fbc87', '');
    }
}
