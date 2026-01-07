<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* __string_template__b202fec5f3c46804b8e54c1190f4e49a */
class __TwigTemplate_1f29954113def03a9ec558e356c409b6 extends Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__b202fec5f3c46804b8e54c1190f4e49a');
        // line 1
        $this->loadTemplate('__string_template__b202fec5f3c46804b8e54c1190f4e49a', '__string_template__b202fec5f3c46804b8e54c1190f4e49a', 1, '544769359')->display(twig_array_merge($context, ['id' => 'foo', 'labelId' => 'label']));
        craft\helpers\Template::endProfile('template', '__string_template__b202fec5f3c46804b8e54c1190f4e49a');
    }

    public function getTemplateName()
    {
        return '__string_template__b202fec5f3c46804b8e54c1190f4e49a';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [38 => 1];
    }

    public function getSourceContext()
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
class __TwigTemplate_1f29954113def03a9ec558e356c409b6___544769359 extends Template
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
    protected function doGetParent(array $context)
    {
        return '_includes/forms/field';
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('template', '__string_template__b202fec5f3c46804b8e54c1190f4e49a');
        $this->parent = $this->loadTemplate('_includes/forms/field', '__string_template__b202fec5f3c46804b8e54c1190f4e49a', 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
        craft\helpers\Template::endProfile('template', '__string_template__b202fec5f3c46804b8e54c1190f4e49a');
    }

    // line 5
    public function block_attr($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'attr');
        echo 'data-foo="test"';
        craft\helpers\Template::endProfile('block', 'attr');
    }

    // line 6
    public function block_heading($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'heading');
        echo 'TEST HEADING';
        $this->displayParentBlock('heading', $context, $blocks);
        craft\helpers\Template::endProfile('block', 'heading');
    }

    // line 7
    public function block_label($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'label');
        echo 'TEST LABEL';
        craft\helpers\Template::endProfile('block', 'label');
    }

    // line 8
    public function block_instructions($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'instructions');
        echo '<p>TEST INSTRUCTIONS</p>';
        craft\helpers\Template::endProfile('block', 'instructions');
    }

    // line 9
    public function block_tip($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'tip');
        echo 'TEST TIP';
        craft\helpers\Template::endProfile('block', 'tip');
    }

    // line 10
    public function block_warning($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'warning');
        echo 'TEST WARNING';
        craft\helpers\Template::endProfile('block', 'warning');
    }

    // line 11
    public function block_input($context, array $blocks = [])
    {
        craft\helpers\Template::beginProfile('block', 'input');
        echo '<input name="foo">';
        craft\helpers\Template::endProfile('block', 'input');
    }

    public function getTemplateName()
    {
        return '__string_template__b202fec5f3c46804b8e54c1190f4e49a';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [168 => 11,  159 => 10,  150 => 9,  141 => 8,  132 => 7,  122 => 6,  113 => 5,  38 => 1];
    }

    public function getSourceContext()
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
