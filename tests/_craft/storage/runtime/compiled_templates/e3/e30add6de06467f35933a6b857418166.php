<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _components/utilities/QueueManager/footer.twig */
class __TwigTemplate_708805eb7aa7dd6ab1de757ed358d1d2 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/QueueManager/footer.twig');
        // line 1
        yield '<p v-if="!activeJob && jobs.length">
    <template v-if="totalJobs == 1">';
        // line 2
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('1 job', 'app'), 'html', null, true);
        yield '</template>
    <template v-else>';
        // line 3
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('{total} jobs', 'app', ['total' => '[[totalJobsFormatted]]']), 'html', null, true);
        yield '</template>
</p>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/QueueManager/footer.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/QueueManager/footer.twig';
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
        return [50 => 3,  46 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("<p v-if=\"!activeJob && jobs.length\">
    <template v-if=\"totalJobs == 1\">{{ '1 job'|t('app') }}</template>
    <template v-else>{{ '{total} jobs'|t('app', {total: '[[totalJobsFormatted]]'}) }}</template>
</p>
", '_components/utilities/QueueManager/footer.twig', '/tmp/packages/craft5/src/templates/_components/utilities/QueueManager/footer.twig');
    }
}
