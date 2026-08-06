<script setup lang="ts">
  import '@craftcms/ui/components/field-group/field-group';
  import {onMounted, ref} from 'vue';
  import FormNode from './FormNode.vue';
  import {inputName} from './runtime';
  import type {FormChange, FormNodePayload, FormPayload} from './types';

  const props = defineProps<{
    node: FormNodePayload<{label: string}>;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
    initiallyHidden?: boolean;
  }>();
  const root = ref<HTMLElement>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange): void;
  }>();

  onMounted(() => {
    if (props.initiallyHidden) {
      root.value?.classList.add('hidden');
    }
  });

  function id(): string {
    const id = `form-tab-${props.node.uid}`;

    return props.scope.length
      ? Craft.namespaceId(id, inputName(props.scope))
      : id;
  }
</script>

<template>
  <section
    ref="root"
    :id="id()"
    :aria-label="node.props.label"
    :data-id="id()"
    :data-form-tab="node.uid"
    :data-layout-tab="node.uid"
  >
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
