<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _components/utilities/QueueManager/toolbar.twig */
class __TwigTemplate_d2a56eb8113504665132ae83ec329e46 extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/QueueManager/toolbar.twig');
        // line 1
        echo '<template v-if="activeJob">
    <button type="button" class="btn" @click="clearActiveJob(true)" data-icon="larr" title="';
        // line 2
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Back to the queue index', 'app'), 'html', null, true);
        echo '">';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Back', 'app'), 'html', null, true);
        echo '</button>
    <div class="flex-grow"></div>
    <div v-if="loading" class="spinner"></div>
    <button type="button" v-if="isRetryable(activeJob)" class="btn" data-icon="play" @click="retryActiveJob">
        <template v-if="activeJob.status == 2">';
        // line 6
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Restart job', 'app'), 'html', null, true);
        echo '</template>
        <template v-else>';
        // line 7
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Retry', 'app'), 'html', null, true);
        echo '</template>
    </button>
    <button v-if="activeJob.status != 3" class="btn" data-icon="remove" @click="releaseActiveJob">';
        // line 9
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Release job', 'app'), 'html', null, true);
        echo '</button>
</template>
<template v-else-if="jobs.length">
    <div class="flex-grow"></div>
    <button type="button" class="btn" data-icon="play" @click="retryAll">';
        // line 13
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Retry all failed jobs', 'app'), 'html', null, true);
        echo '</button>
    <button type="button" class="btn" data-icon="remove" @click="releaseAll">';
        // line 14
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Release all jobs', 'app'), 'html', null, true);
        echo '</button>
</template>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/QueueManager/toolbar.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/QueueManager/toolbar.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [70 => 14,  66 => 13,  59 => 9,  54 => 7,  50 => 6,  41 => 2,  38 => 1];
    }

    public function getSourceContext()
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
", '_components/utilities/QueueManager/toolbar.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/QueueManager/toolbar.twig');
    }
}
