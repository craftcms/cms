<script setup lang="ts">
  /**
   * One import step's field mapping, as a slideout panel.
   *
   * Opened from the step's own panel — see `step-mapping.ts` — or on a page of its own.
   * The panel edits its own copy of the step's mapping trees and hands them back on
   * Apply, so canceling leaves the step behind untouched.
   */
  import {t} from '@craftcms/ui';
  import CpContainer from '@/common/components/CpContainer.vue';
  import MappingTable from '@/modules/import/mapping/MappingTable.vue';
  import MatchingSection from '@/modules/import/mapping/MatchingSection.vue';
  import {useMappingPanel} from '@/modules/import/mapping/useMappingPanel';
  import {
    takeStepMappingContext,
    type StepMappingContext,
  } from './step-mapping';

  const props = defineProps<{
    /** A slideout claims its context by id; a page hands it over directly. */
    contextId?: string;
    context?: StepMappingContext;
    title: string;
  }>();

  const context = props.context ?? takeStepMappingContext(props.contextId!);

  useMappingPanel({
    title: props.title,
    values: context.values,
    suggestedMap: context.suggestedMap,
    sourceDataCols: context.sourceDataCols,
    templateTextExpanderTriggers: context.templateTextExpanderTriggers,
    editable: context.editable,
    step: context.step,
    cols: context.destinationCols,
    editUrl: context.editUrl,
    submitLabel: context.submitLabel,
    apply: context.apply,
  });
</script>

<template>
  <craft-field-group class="py-md">
    <MappingTable :cols="context.destinationCols" />

    <CpContainer>
      <MatchingSection :cols="context.destinationCols" />
    </CpContainer>
  </craft-field-group>
</template>
