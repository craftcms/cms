<script setup lang="ts">
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/field-group/field-group';
  import '@craftcms/ui/components/disclosure/disclosure';
  import '@craftcms/ui/components/spinner/spinner';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, inject} from 'vue';
  import {useDelayedLoading} from '@/common/composables/useDelayedLoading';
  import UiNodeList from './UiNodeList.vue';
  import {UiRefreshingFields} from './runtime';
  import type {UiChange, UiNodePayload, UiPayload} from './types';

  type GroupNodeProps = {
    label?: string | null;
    collapsible?: boolean;
    expanded?: boolean;
    /** Renders the group as one field rather than a section — see `Nodes\Group`. */
    asField?: boolean;
    required?: boolean;
    instructions?: string | null;
    instructionsPosition?: 'before' | 'after';
    tip?: string;
    tipHtml?: string;
    warning?: string;
    warningHtml?: string;
    layoutUid?: string;
    width?: number;
    /** Absolute path of the reactive control whose refresh loads this group. */
    dependsOn?: string[];
    /** Hidden from view; children still resolve and still hold their values. */
    hidden?: boolean;
  };

  const props = defineProps<{
    node: UiNodePayload<GroupNodeProps>;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: UiChange): void;
  }>();
  const refreshingFields = inject(UiRefreshingFields, undefined);
  const loading = computed(() => {
    if (!props.node.props.dependsOn) {
      return false;
    }

    return Boolean(
      refreshingFields?.value.has(JSON.stringify(props.node.props.dependsOn))
    );
  });
  const showLoading = useDelayedLoading(loading);
</script>

<template>
  <craft-field
    v-if="node.props.asField"
    fieldset
    :label="node.props.label ?? undefined"
    :required="node.props.required || undefined"
    :help-text="node.props.instructions ?? undefined"
    :instructions-position="node.props.instructionsPosition"
    :class="{
      [`width-${node.props.width}`]: Boolean(node.props.width),
      hidden: Boolean(node.props.hidden),
      'group-container': true,
    }"
    :hidden="node.props.hidden || undefined"
    :data-ui-node="node.uid"
    :data-layout-element="node.props.layoutUid"
    :aria-busy="showLoading || undefined"
  >
    <span v-if="node.props.tipHtml" slot="tip" v-html="node.props.tipHtml" />
    <span
      v-if="node.props.warningHtml"
      slot="warning"
      v-html="node.props.warningHtml"
    />
    <craft-field-group
      slot="input"
      :class="{
        'auto-widths': true,
        'group-fields-loading': showLoading,
      }"
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
    <craft-spinner
      v-if="showLoading"
      slot="input"
      class="group-spinner"
      role="status"
    >
      {{ t('Loading') }}
    </craft-spinner>
  </craft-field>
  <component
    v-else
    :is="node.props.collapsible ? 'craft-disclosure' : 'fieldset'"
    :label="node.props.collapsible ? node.props.label : undefined"
    .opened="
      node.props.collapsible ? (node.props.expanded ?? false) : undefined
    "
    :class="{
      [`width-${node.props.width}`]: Boolean(node.props.width),
      hidden: Boolean(node.props.hidden),
      'group-container': true,
    }"
    :hidden="node.props.hidden || undefined"
    :data-ui-node="node.uid"
    :aria-busy="showLoading || undefined"
  >
    <legend v-if="!node.props.collapsible && node.props.label">
      {{ node.props.label }}
    </legend>
    <craft-field-group
      :slot="node.props.collapsible ? 'content' : undefined"
      :class="{'group-fields-loading': showLoading}"
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
    <craft-spinner
      v-if="showLoading"
      :slot="node.props.collapsible ? 'content' : undefined"
      class="group-spinner"
      role="status"
    >
      {{ t('Loading') }}
    </craft-spinner>
  </component>
</template>

<style scoped>
  .group-container {
    position: relative;
  }

  /* Dims the fields rather than the group: in Chrome, changing a
     container-type group's opacity inside a fieldset while the spinner is
     added can leave the group's children without layout boxes. */
  .group-fields-loading > :deep(*) {
    opacity: 0.5;
  }

  .group-spinner {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: wait;
  }
</style>
