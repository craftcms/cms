<script setup lang="ts">
  import '@craftcms/ui/components/field-group/field-group';
  import FormNode from './FormNode.vue';
  import type {FormNodePayload, FormPayload} from './types';

  type GroupNodeProps = {label?: string | null};

  defineProps<{
    node: FormNodePayload<GroupNodeProps>;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
  }>();
</script>

<template>
  <fieldset :data-form-node="node.uid">
    <legend v-if="node.props.label">{{ node.props.label }}</legend>
    <craft-field-group>
      <FormNode
        v-for="child in node.children"
        :key="child.uid ?? child.control?.path.join('.')"
        :node="child"
        :values="values"
        :errors="errors"
      />
    </craft-field-group>
  </fieldset>
</template>
