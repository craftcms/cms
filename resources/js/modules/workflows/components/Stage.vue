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
    :appearance="isCollapsible ? 'plain' : undefined"
    :open.prop="isCollapsible ? stage.current : undefined"
  >
    <craft-button
      v-if="isCollapsible"
      slot="invoker"
      type="button"
      variant="plain"
      size="small"
      align="start"
      class="workflow-review-stage__heading"
    >
      <span class="workflow-review-stage__label">
        <strong>{{ number }}. {{ stage.name }}</strong>
        <span class="workflow-review-stage__status">
          <span class="sr-only">, </span>
          {{ stage.statusLabel }}
          <!-- The status text beside it says the same. -->
          <span class="workflow-review-stage__dot" aria-hidden="true">
            <craft-status :status="stage.status"></craft-status>
          </span>
        </span>
      </span>
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
      <strong>{{ number }}. {{ stage.name }}</strong>
      <span class="workflow-review-stage__status">
        <span class="sr-only">, </span>
        {{ stage.statusLabel }}
        <!-- The status text beside it says the same. -->
        <span class="workflow-review-stage__dot" aria-hidden="true">
          <craft-status :status="stage.status"></craft-status>
        </span>
      </span>
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
    display: flex;
    margin-inline: calc(-1 * var(--c-spacing-md));
    background-color: var(--c-color-neutral-fill-quiet);
    --c-button-radius: 0;
  }

  craft-button.workflow-review-stage__heading {
    color: var(--c-color-neutral-on-quiet);
    min-height: calc(var(--c-size-control-sm) + 2 * var(--c-spacing-xs));
    padding-inline: calc(var(--c-spacing-md) + var(--c-spacing-xs) - 1px);
  }

  .workflow-review-stage__heading::part(prefix) {
    display: none;
  }

  craft-button.workflow-review-stage__heading:hover {
    background-color: var(--c-color-neutral-fill-normal);
  }

  craft-button.workflow-review-stage__heading:active {
    background-color: color-mix(
      in oklab,
      var(--c-color-neutral-fill-normal),
      var(--c-color-mix-active)
    );
    box-shadow: none;
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
    grid-template-columns: minmax(0, 1fr) auto;
    min-height: calc(var(--c-size-control-sm) + 2 * var(--c-spacing-xs));
    padding-block: var(--c-spacing-xs);
    padding-inline: calc(var(--c-spacing-md) + var(--c-spacing-xs));
  }

  .workflow-review-stage__dot {
    display: flex;
  }

  .workflow-review-stage__label {
    display: flex;
    width: 100%;
    align-items: baseline;
    gap: var(--c-spacing-sm);
  }

  .workflow-review-stage__status {
    display: flex;
    margin-inline-start: auto;
    align-items: center;
    gap: var(--c-spacing-md);
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
    font-weight: normal;
    white-space: nowrap;
  }

  .workflow-review-stage__chevron {
    transform: rotate(-90deg);
    transition: transform 100ms ease;

    &:dir(rtl) {
      transform: rotate(90deg);
    }
  }

  .workflow-review-stage[open] .workflow-review-stage__chevron {
    transform: none;
  }

  .workflow-review-stage__content,
  .workflow-review-events {
    display: grid;
    gap: var(--c-spacing-md);
  }

  .workflow-review-stage__content {
    margin-block-start: var(--c-spacing-md);
    padding-block-end: var(--c-spacing-md);
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
