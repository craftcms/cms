<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _components/utilities/QueueManager/toolbar.twig */
class __TwigTemplate_19fbb6b8f02c8e115c556cd3af0c88ff extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/QueueManager/toolbar.twig');
        // line 1
        yield '<template v-if="activeJob">
    <button type="button" class="btn" @click="clearActiveJob(true)" data-icon="larr" title="';
        // line 2
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Back to the queue index', 'app'), 'html', null, true);
        yield '">';
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Back', 'app'), 'html', null, true);
        yield '</button>
    <div class="flex-grow"></div>
    <div v-if="loading" class="spinner"></div>
    <button type="button" v-if="isRetryable(activeJob)" class="btn" data-icon="play" @click="retryActiveJob">
        <template v-if="activeJob.status == 2">';
        // line 6
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Restart job', 'app'), 'html', null, true);
        yield '</template>
        <template v-else>';
        // line 7
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Retry', 'app'), 'html', null, true);
        yield '</template>
    </button>
    <button v-if="activeJob.status != 3" class="btn" data-icon="remove" @click="releaseActiveJob">';
        // line 9
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Release job', 'app'), 'html', null, true);
        yield '</button>
</template>
<template v-else-if="jobs.length">
    <div class="flex-grow"></div>
    <button type="button" class="btn" data-icon="play" @click="retryAll">';
        // line 13
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Retry all failed jobs', 'app'), 'html', null, true);
        yield '</button>
    <button type="button" class="btn" data-icon="remove" @click="releaseAll">';
        // line 14
        yield $this->env->getRuntime(\Twig\Runtime\EscaperRuntime::class)->escape($this->extensions['craft\web\twig\Extension']->translateFilter('Release all jobs', 'app'), 'html', null, true);
        yield '</button>
</template>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/QueueManager/toolbar.twig');
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return '_components/utilities/QueueManager/toolbar.twig';
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
        return [75 => 14,  71 => 13,  64 => 9,  59 => 7,  55 => 6,  46 => 2,  43 => 1];
    }

    public function getSourceContext(): Source
    {
        return new Source("<template v-if=\"activeJob\">
    <button type=\"button\" class=\"btn\" @click=\"clearActiveJob(true)\" data-icon=\"larr\" title=\"{{ 'Back to the queue index'|t('app') }}\">{{ 'Back'|t('app') }}</button>
    <div class=\"flex-grow\"></div>
    <div v-if=\"loading\" class=\"spinner\"></div>
    <button type=\"button\" v-if=\"isRetryable(activeJob)\" class=\"btn\" data-icon=\"play\" @click=\"retryActiveJob\">
        <template v-if=\"activeJob.status == 2\">{{ 'Restart job'|t('app') }}</template>
        <template v-else>{{ 'Retry'|t('app') }}</template>
    </button>
    <button v-if=\"activeJob.status != 3\" class=\"btn\" data-icon=\"remove\" @click=\"releaseActiveJob\">{{ 'Release job'|t('app') }}</button>
</template>
<template v-else-if=\"jobs.length\">
    <div class=\"flex-grow\"></div>
    <button type=\"button\" class=\"btn\" data-icon=\"play\" @click=\"retryAll\">{{ 'Retry all failed jobs'|t('app') }}</button>
    <button type=\"button\" class=\"btn\" data-icon=\"remove\" @click=\"releaseAll\">{{ 'Release all jobs'|t('app') }}</button>
</template>
", '_components/utilities/QueueManager/toolbar.twig', '/tmp/packages/craft5/src/templates/_components/utilities/QueueManager/toolbar.twig');
    }
}
