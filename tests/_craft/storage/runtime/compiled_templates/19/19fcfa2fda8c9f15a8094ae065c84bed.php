<?php

use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* __string_template__b202fec5f3c46804b8e54c1190f4e49a */
class __TwigTemplate_cf632e1e4b6e0acbf1e99652dacbc103 extends Template
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
        craft\helpers\Template::beginProfile('template', '__string_template__b202fec5f3c46804b8e54c1190f4e49a');
        // line 1
        yield from $this->loadTemplate('__string_template__b202fec5f3c46804b8e54c1190f4e49a', '__string_template__b202fec5f3c46804b8e54c1190f4e49a', 1, '661381367')->unwrap()->yield(CoreExtension::merge($context, ['id' => 'foo', 'labelId' => 'label']));
        craft\helpers\Template::endProfile('template', '__string_template__b202fec5f3c46804b8e54c1190f4e49a');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__b202fec5f3c46804b8e54c1190f4e49a';
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
        return new Source("{% embed '_includes/forms/field' with {
  id: 'foo',
  labelId: 'label',
} %}
  {% block attr %}data-foo=\"test\"{% endblock %}
  {% block heading %}TEST HEADING{{ parent() }}{% endblock %}
  {% block label %}TEST LABEL{% endblock %}
  {% block instructions %}<p>TEST INSTRUCTIONS</p>{% endblock %}
  {% block tip %}TEST TIP{% endblock %}
  {% block warning %}TEST WARNING{% endblock %}
  {% block input %}<input name=\"foo\">{% endblock %}
{% endembed %}", '__string_template__b202fec5f3c46804b8e54c1190f4e49a', '');
    }
}

/* __string_template__b202fec5f3c46804b8e54c1190f4e49a */
class __TwigTemplate_cf632e1e4b6e0acbf1e99652dacbc103___661381367 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'attr' => $this->block_attr(...),
            'heading' => $this->block_heading(...),
            'label' => $this->block_label(...),
            'instructions' => $this->block_instructions(...),
            'tip' => $this->block_tip(...),
            'warning' => $this->block_warning(...),
            'input' => $this->block_input(...),
        ];
    }

    #[\Override]
    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        return '_includes/forms/field';
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('template', '__string_template__b202fec5f3c46804b8e54c1190f4e49a');
        $this->parent = $this->loadTemplate('_includes/forms/field', '__string_template__b202fec5f3c46804b8e54c1190f4e49a', 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '__string_template__b202fec5f3c46804b8e54c1190f4e49a');
    }

    // line 5
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_attr(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'attr');
        yield 'data-foo="test"';
        craft\helpers\Template::endProfile('block', 'attr');
        yield from [];
    }

    // line 6
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_heading(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'heading');
        yield 'TEST HEADING';
        yield from $this->yieldParentBlock('heading', $context, $blocks);
        craft\helpers\Template::endProfile('block', 'heading');
        yield from [];
    }

    // line 7
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_label(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'label');
        yield 'TEST LABEL';
        craft\helpers\Template::endProfile('block', 'label');
        yield from [];
    }

    // line 8
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_instructions(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'instructions');
        yield '<p>TEST INSTRUCTIONS</p>';
        craft\helpers\Template::endProfile('block', 'instructions');
        yield from [];
    }

    // line 9
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_tip(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'tip');
        yield 'TEST TIP';
        craft\helpers\Template::endProfile('block', 'tip');
        yield from [];
    }

    // line 10
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_warning(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'warning');
        yield 'TEST WARNING';
        craft\helpers\Template::endProfile('block', 'warning');
        yield from [];
    }

    // line 11
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_input(array $context, array $blocks = []): iterable
    {
        craft\helpers\Template::beginProfile('block', 'input');
        yield '<input name="foo">';
        craft\helpers\Template::endProfile('block', 'input');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '__string_template__b202fec5f3c46804b8e54c1190f4e49a';
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
        return [210 => 11,  197 => 10,  184 => 9,  171 => 8,  158 => 7,  144 => 6,  131 => 5,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("{% embed '_includes/forms/field' with {
  id: 'foo',
  labelId: 'label',
} %}
  {% block attr %}data-foo=\"test\"{% endblock %}
  {% block heading %}TEST HEADING{{ parent() }}{% endblock %}
  {% block label %}TEST LABEL{% endblock %}
  {% block instructions %}<p>TEST INSTRUCTIONS</p>{% endblock %}
  {% block tip %}TEST TIP{% endblock %}
  {% block warning %}TEST WARNING{% endblock %}
  {% block input %}<input name=\"foo\">{% endblock %}
{% endembed %}", '__string_template__b202fec5f3c46804b8e54c1190f4e49a', '');
    }
}
