<script setup lang="ts">
  import '@craftcms/ui/components/field-group/field-group';
  import '@craftcms/ui/components/spinner/spinner';
  import {t} from '@craftcms/ui';
  import {inject} from 'vue';
  import UiNodeList from './UiNodeList.vue';
  import {scopedId, UiFieldIdScope, uiTabPanelId} from './runtime';
  import type {UiChange, UiNodePayload, UiPayload} from './types';

  const props = defineProps<{
    /** `deferred` when the server held its children back, to send once the page has loaded. */
    node: UiNodePayload<{label: string; deferred?: boolean}>;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
    initiallyHidden?: boolean;
    tabButtonId?: string;
    /** Leaves the panel empty, until it's first shown. */
    deferred?: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: UiChange): void;
  }>();

  const idScope = inject(UiFieldIdScope, undefined);

  function id(): string {
    return scopedId(idScope, uiTabPanelId(props.node.uid!, props.scope));
  }
</script>

<template>
  <section
    :id="id()"
    :class="{hidden: initiallyHidden}"
    :role="tabButtonId ? 'tabpanel' : undefined"
    :aria-label="node.props.label"
    :aria-labelledby="tabButtonId"
    :data-id="id()"
    :data-ui-tab="node.uid"
    :data-layout-tab="node.uid"
  >
    <div
      v-if="!deferred && node.props.deferred"
      class="flex justify-center py-lg"
    >
      <craft-spinner :label="t('Loading')" />
    </div>
    <craft-field-group
      v-else-if="!deferred"
      separated
      style="--c-field-group-separator-spacing: var(--cp-container-padding)"
    >
      <UiNodeList
        :nodes="node.children ?? []"
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
