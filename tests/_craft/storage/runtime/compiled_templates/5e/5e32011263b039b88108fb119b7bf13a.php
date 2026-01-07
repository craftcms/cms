<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _components/utilities/QueueManager/footer.twig */
class __TwigTemplate_a5bb1b04e31de25296720a46ac8da8ef extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/QueueManager/footer.twig');
        // line 1
        echo '<p v-if="!activeJob && jobs.length">
    <template v-if="totalJobs == 1">';
        // line 2
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('1 job', 'app'), 'html', null, true);
        echo '</template>
    <template v-else>';
        // line 3
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('{total} jobs', 'app', ['total' => '[[totalJobsFormatted]]']), 'html', null, true);
        echo '</template>
</p>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/QueueManager/footer.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/QueueManager/footer.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [45 => 3,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<p v-if=\"!activeJob && jobs.length\">
    <template v-if=\"totalJobs == 1\">{{ '1 job'|t('app') }}</template>
    <template v-else>{{ '{total} jobs'|t('app', {total: '[[totalJobsFormatted]]'}) }}</template>
</p>
", '_components/utilities/QueueManager/footer.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/QueueManager/footer.twig');
    }
}
