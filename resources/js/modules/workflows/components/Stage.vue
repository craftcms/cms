<script setup lang="ts">
  import '@craftcms/ui/components/disclosure/disclosure';
  import {computed} from 'vue';
  import TimelineItem from './TimelineItem.vue';

  type WorkflowReviewData = CraftCms.Cms.Workflow.Data.WorkflowReviewData;
  type WorkflowRun = WorkflowReviewData['runs'][number];
  type WorkflowStage = WorkflowRun['stages'][number];

  const props = defineProps<{
    run: WorkflowRun;
    stage: WorkflowStage;
    number: number;
    collapsible: boolean;
  }>();

  const hasContent = computed(
    () =>
      Boolean(props.stage.message) ||
      props.stage.events.length > 0 ||
      Boolean(props.stage.summaryComponent)
  );
  const isCollapsible = computed(() => props.collapsible && hasContent.value);
</script>

<template>
  <component
    :is="isCollapsible ? 'craft-disclosure' : 'section'"
    class="workflow-review-stage"
    :opened.prop="isCollapsible ? stage.current : undefined"
  >
    <craft-button
      v-if="isCollapsible"
      slot="invoker"
      type="button"
      variant="plain"
      align="start"
      class="workflow-review-stage__heading"
    >
      <craft-icon
        slot="prefix"
        :name="stage.icon"
        appearance="badge"
        :data-color="stage.iconColor"
      />
      <strong>{{ number }}. {{ stage.name }}</strong>
      <craft-icon
        slot="suffix"
        class="workflow-review-stage__chevron"
        name="chevron-down"
      />
    </craft-button>

    <div
      v-else
      class="workflow-review-stage__heading workflow-review-stage__heading--static"
    >
      <craft-icon
        :name="stage.icon"
        appearance="badge"
        :data-color="stage.iconColor"
      />
      <strong>{{ number }}. {{ stage.name }}</strong>
    </div>

    <div
      v-if="hasContent"
      :slot="isCollapsible ? 'content' : undefined"
      class="workflow-review-stage__content"
    >
      <p v-if="stage.message" class="workflow-review-stage__message">
        {{ stage.message }}
      </p>

      <ol v-if="stage.events.length" class="workflow-review-events">
        <li v-for="(event, eventIndex) in stage.events" :key="event.id">
          <TimelineItem
            :event="event"
            :last="eventIndex === stage.events.length - 1"
          />
        </li>
      </ol>

      <component
        :is="stage.summaryComponent"
        v-if="stage.summaryComponent"
        v-bind="stage.summaryProps"
        :run="run"
        :stage="stage"
      />
    </div>
  </component>
</template>

<style scoped>
  .workflow-review-stage__heading {
    width: 100%;
    --c-button-height: var(--c-size-touch-target);
    --c-button-spacing-inline: var(--c-spacing-xs);
  }

  .workflow-review-stage__heading::part(content) {
    gap: var(--c-spacing-sm);
  }

  .workflow-review-stage__heading::part(label) {
    flex: 1;
    min-width: 0;
    text-align: start;
  }

  .workflow-review-stage__heading--static {
    display: grid;
    align-items: center;
    gap: var(--c-spacing-sm);
    grid-template-columns: auto minmax(0, 1fr);
    min-height: var(--c-size-touch-target);
    padding: var(--c-spacing-xs);
  }

  .workflow-review-stage__chevron {
    transition: transform 100ms ease;
  }

  .workflow-review-stage[opened] .workflow-review-stage__chevron {
    transform: rotate(180deg);
  }

  .workflow-review-stage__content,
  .workflow-review-events {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .workflow-review-stage__content {
    margin-block-start: var(--c-spacing-xs);
    padding-block-end: var(--c-spacing-sm);
    padding-inline-start: 0;
    border-inline-start: 0;
  }

  .workflow-review-events {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .workflow-review-stage__message {
    margin: 0;
    padding-inline: var(--c-spacing-xs);
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }
</style>
