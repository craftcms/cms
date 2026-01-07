<?php

use Twig\Environment;
use Twig\Source;
use Twig\Template;

/* _components/utilities/QueueManager/content.twig */
class __TwigTemplate_b7ead7cb3d049c9bb8f240b229ac1f7f extends Template
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
        craft\helpers\Template::beginProfile('template', '_components/utilities/QueueManager/content.twig');
        // line 1
        echo "<div id=\"queue-manager-utility\" class=\"hidden\">
    <div v-if=\"activeJob\" class=\"readable\">
        <h2>[[activeJob.description]]</h2>

        <table class=\"data fullwidth fixed-layout\">
            <tbody>
            <tr v-for=\"(value, name) in activeJob\" v-if=\"!Craft.inArray(name, ['delay', 'description', 'progressLabel', 'job'])\">
                <th class=\"light\">[[jobAttributeName(name)]]</th>
                <td :class=\"(name == 'status' || name == 'error') ? jobStatusClass(activeJob.status) : null\">
                    <template v-if=\"name == 'status'\">
                        <span :class=\"jobStatusIconClass(value)\"></span>[[jobStatusLabel(value, activeJob.delay)]]
                    </template>
                    <template v-else-if=\"name == 'progress'\">
                        [[activeJob.progress]]%
                        <span v-if=\"activeJob.progressLabel\" class=\"light\">([[activeJob.progressLabel]])</span>
                    </template>
                    <template v-else-if=\"name == 'ttr'\">
                        [[ttrValue(value)]]
                    </template>
                    <template v-else>
                        [[value]]
                    </template>
                </td>
            </tr>
            </tbody>
        </table>

        <template v-if=\"activeJob.job\">
            <hr>
            <h4>";
        // line 30
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Job Data', 'app'), 'html', null, true);
        echo '</h4>
            <pre class="pane"><code>[[activeJob.job]]</code></pre>
        </template>
    </div>
    <template v-else-if="!loading">
        <template v-if="jobs.length">
            <div class="tablepane">
                <table class="data fullwidth" v-if="jobs.length > 0 && !activeJob">
                    <thead>
                        <tr>
                            <th scope="col">';
        // line 40
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Job', 'app'), 'html', null, true);
        echo '</th>
                            <th scope="col">';
        // line 41
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Status', 'app'), 'html', null, true);
        echo '</th>
                            <th scope="col">';
        // line 42
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Progress', 'app'), 'html', null, true);
        echo '</th>
                            <th scope="col" class="thin"></th>
                            <th scope="col" class="thin"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="job in jobs">
                            <th><a @click="setActiveJob(job.id, true)">[[job.description]]</a></th>
                            <td :class="jobStatusClass(job.status)">
                                <span :class="jobStatusIconClass(job.status)"></span>[[jobStatusLabel(job.status, job.delay)]]
                            </td>
                            <td>
                                <template v-if="job.status == 2">
                                    [[job.progress]]%
                                    <span v-if="job.progressLabel" class="light">([[job.progressLabel]])</span>
                                </template>
                            </td>
                            <td>
                                <button type="button" v-if="isRetryable(job)" class="btn small" data-icon="play" @click="retryJob(job)">
                                    <template v-if="job.status == 2">';
        // line 61
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Restart', 'app'), 'html', null, true);
        echo '</template>
                                    <template v-else>';
        // line 62
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Retry', 'app'), 'html', null, true);
        echo '</template>
                                </button>
                            </td>
                            <td>
                                <a v-if="job.status != 3" class="delete icon" title="';
        // line 66
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Release', 'app'), 'html', null, true);
        echo '" aria-label="';
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('Release', 'app'), 'html', null, true);
        echo '" role="button" @click="releaseJob(job)"></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
        <div v-else class="zilch">
            <p>';
        // line 74
        echo twig_escape_filter($this->env, $this->extensions['craft\web\twig\Extension']->translateFilter('No pending jobs.', 'app'), 'html', null, true);
        echo '</p>
        </div>
    </template>
</div>
';
        craft\helpers\Template::endProfile('template', '_components/utilities/QueueManager/content.twig');
    }

    public function getTemplateName()
    {
        return '_components/utilities/QueueManager/content.twig';
    }

    #[\Override]
    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return [136 => 74,  123 => 66,  116 => 62,  112 => 61,  90 => 42,  86 => 41,  82 => 40,  69 => 30,  38 => 1];
    }

    public function getSourceContext()
    {
        return new Source("<div id=\"queue-manager-utility\" class=\"hidden\">
    <div v-if=\"activeJob\" class=\"readable\">
        <h2>[[activeJob.description]]</h2>

        <table class=\"data fullwidth fixed-layout\">
            <tbody>
            <tr v-for=\"(value, name) in activeJob\" v-if=\"!Craft.inArray(name, ['delay', 'description', 'progressLabel', 'job'])\">
                <th class=\"light\">[[jobAttributeName(name)]]</th>
                <td :class=\"(name == 'status' || name == 'error') ? jobStatusClass(activeJob.status) : null\">
                    <template v-if=\"name == 'status'\">
                        <span :class=\"jobStatusIconClass(value)\"></span>[[jobStatusLabel(value, activeJob.delay)]]
                    </template>
                    <template v-else-if=\"name == 'progress'\">
                        [[activeJob.progress]]%
                        <span v-if=\"activeJob.progressLabel\" class=\"light\">([[activeJob.progressLabel]])</span>
                    </template>
                    <template v-else-if=\"name == 'ttr'\">
                        [[ttrValue(value)]]
                    </template>
                    <template v-else>
                        [[value]]
                    </template>
                </td>
            </tr>
            </tbody>
        </table>

        <template v-if=\"activeJob.job\">
            <hr>
            <h4>{{ 'Job Data'|t('app') }}</h4>
            <pre class=\"pane\"><code>[[activeJob.job]]</code></pre>
        </template>
    </div>
    <template v-else-if=\"!loading\">
        <template v-if=\"jobs.length\">
            <div class=\"tablepane\">
                <table class=\"data fullwidth\" v-if=\"jobs.length > 0 && !activeJob\">
                    <thead>
                        <tr>
                            <th scope=\"col\">{{ 'Job'|t('app') }}</th>
                            <th scope=\"col\">{{ 'Status'|t('app') }}</th>
                            <th scope=\"col\">{{ 'Progress'|t('app') }}</th>
                            <th scope=\"col\" class=\"thin\"></th>
                            <th scope=\"col\" class=\"thin\"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for=\"job in jobs\">
                            <th><a @click=\"setActiveJob(job.id, true)\">[[job.description]]</a></th>
                            <td :class=\"jobStatusClass(job.status)\">
                                <span :class=\"jobStatusIconClass(job.status)\"></span>[[jobStatusLabel(job.status, job.delay)]]
                            </td>
                            <td>
                                <template v-if=\"job.status == 2\">
                                    [[job.progress]]%
                                    <span v-if=\"job.progressLabel\" class=\"light\">([[job.progressLabel]])</span>
                                </template>
                            </td>
                            <td>
                                <button type=\"button\" v-if=\"isRetryable(job)\" class=\"btn small\" data-icon=\"play\" @click=\"retryJob(job)\">
                                    <template v-if=\"job.status == 2\">{{ 'Restart'|t('app') }}</template>
                                    <template v-else>{{ 'Retry'|t('app') }}</template>
                                </button>
                            </td>
                            <td>
                                <a v-if=\"job.status != 3\" class=\"delete icon\" title=\"{{ 'Release'|t('app') }}\" aria-label=\"{{ 'Release'|t('app') }}\" role=\"button\" @click=\"releaseJob(job)\"></a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
        <div v-else class=\"zilch\">
            <p>{{ 'No pending jobs.'|t('app') }}</p>
        </div>
    </template>
</div>
", '_components/utilities/QueueManager/content.twig', '/Users/brianhanson/Development/craft5/src/templates/_components/utilities/QueueManager/content.twig');
    }
}
