<script setup lang="ts">
  /**
   * A plan's Run cell in the import plans table: a button to run it, a spinner linking to
   * its job in the queue manager while it runs, and a warning when its last run failed.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/spinner/spinner';
  import '@craftcms/ui/components/tooltip/tooltip';
  import {computed, useId} from 'vue';
  import {t} from '@craftcms/ui';
  import CpLink from '@/common/components/CpLink.vue';

  type ImportRow = CraftCms.Cms.Import.Data.ImportPlanIndexData;

  const props = defineProps<{
    plan: ImportRow;
    /** Whether the user can run import plans. */
    canRun: boolean;
  }>();

  const emit = defineEmits<{
    run: [];
  }>();

  const id = useId();

  const label = computed(() =>
    props.plan.lastRunFailed
      ? t('The last run of “{name}” had errors. Run it again', {
          name: props.plan.name,
        })
      : t('Run “{name}”', {name: props.plan.name})
  );
</script>

<template>
  <CpLink
    v-if="plan.running && plan.runningJobUrl"
    :href="plan.runningJobUrl"
    class="import-plan-run"
    :aria-label="
      t('“{name}” is running. View it in the queue manager', {name: plan.name})
    "
  >
    <craft-spinner aria-hidden="true"></craft-spinner>
  </CpLink>

  <template v-else-if="canRun">
    <craft-button
      :id="id"
      type="button"
      size="small"
      variant="plain"
      :icon="plan.lastRunFailed ? 'triangle-exclamation' : 'play'"
      :class="{'import-plan-run--failed': plan.lastRunFailed}"
      :aria-label="label"
      .disabled="plan.stepCount === 0"
      @click="emit('run')"
    ></craft-button>
    <craft-tooltip :for="id">
      {{
        plan.stepCount === 0
          ? t('Add a step to run this import plan.')
          : plan.lastRunFailed
            ? t('The last run had errors. Run again')
            : t('Run')
      }}
    </craft-tooltip>
  </template>

  <craft-icon
    v-else-if="plan.lastRunFailed"
    name="triangle-exclamation"
    class="import-plan-run--failed"
    :label="t('The last run had errors.')"
  ></craft-icon>
</template>

<style scoped>
  .import-plan-run {
    display: inline-flex;
    align-items: center;
  }

  .import-plan-run craft-spinner {
    --size: 16px;
  }

  .import-plan-run--failed {
    color: var(--c-color-danger-on-quiet);
  }
</style>
