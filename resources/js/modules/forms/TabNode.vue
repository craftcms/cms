<script setup lang="ts">
  import '@craftcms/ui/components/field-group/field-group';
  import FormNode from './FormNode.vue';
  import type {FormChange, FormNodePayload, FormPayload} from './types';

  defineProps<{
    node: FormNodePayload<{label: string}>;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange): void;
  }>();
</script>

<template>
  <section :aria-label="node.props.label" :data-form-tab="node.uid">
    <craft-field-group>
      <FormNode
        v-for="child in node.children"
        :key="child.uid ?? child.control?.path.join('.')"
        :node="child"
        :values="values"
        :errors="errors"
        :touched-paths="touchedPaths"
        :scope="scope"
        :refreshable="refreshable"
        @change="emit('change', $event)"
      />
    </craft-field-group>
  </section>
</template>
