<script setup lang="ts">
  /**
   * One import step's field mapping, as a slideout panel.
   *
   * Opened from the step's own panel — see `step-mapping.ts`. The panel edits its own
   * copy of the step's four mapping trees and hands them back on Apply, so cancelling
   * leaves the step behind untouched.
   */
  import {t} from '@craftcms/ui';
  import MappingTable from '@/modules/import/mapping/MappingTable.vue';
  import {useMappingPanel} from '@/modules/import/mapping/useMappingPanel';
  import {takeStepMappingContext} from './step-mapping';

  const props = defineProps<{
    contextId: string;
    title: string;
  }>();

  const context = takeStepMappingContext(props.contextId);

  useMappingPanel({
    title: props.title,
    values: context.values,
    suggestedMap: context.suggestedMap,
    sourceDataCols: context.sourceDataCols,
    editable: context.editable,
    step: context.step,
    nestedColsUrl: context.urls.nestedColsUrl,
    apply: context.apply,
  });
</script>

<template>
  <div>
    <p>
      {{
        t(
          'If you see any selections marked in blue, they were auto-selected as closest matches from the incoming data. Change them if they’re not right.'
        )
      }}
    </p>

    <MappingTable :cols="context.destinationCols" />
  </div>
</template>
