<script setup lang="ts">
  import '@craftcms/ui/components/field/field';
  import '@craftcms/ui/components/field-group/field-group';
  import '@craftcms/ui/components/disclosure/disclosure';
  import '@craftcms/ui/components/spinner/spinner';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, inject, shallowRef, watch} from 'vue';
  import FormNodeList from './FormNodeList.vue';
  import {FormRefreshingFields} from './runtime';
  import type {FormChange, FormNodePayload, FormPayload} from './types';

  type GroupNodeProps = {
    label?: string | null;
    collapsible?: boolean;
    /** Renders the group as one field rather than a section — see `Nodes\Group`. */
    asField?: boolean;
    instructions?: string | null;
    tip?: string;
    tipHtml?: string;
    warning?: string;
    warningHtml?: string;
    width?: number;
    /** Absolute path of the reactive control whose refresh loads this group. */
    dependsOn?: string[];
    /** Hidden from view; children still resolve and still hold their values. */
    hidden?: boolean;
  };

  const props = defineProps<{
    node: FormNodePayload<GroupNodeProps>;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    scope: string[];
    refreshable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'change', change: FormChange): void;
  }>();
  const refreshingFields = inject(FormRefreshingFields, undefined);
  const loading = computed(() => {
    if (!props.node.props.dependsOn) {
      return false;
    }

    return Boolean(
      refreshingFields?.value.has(JSON.stringify(props.node.props.dependsOn))
    );
  });
  const showLoading = shallowRef(false);

  watch(loading, (loading, _, onCleanup) => {
    if (!loading) {
      showLoading.value = false;

      return;
    }

    const timeout = setTimeout(() => {
      showLoading.value = true;
    }, 200);
    onCleanup(() => clearTimeout(timeout));
  });
</script>

<template>
  <craft-field
    v-if="node.props.asField"
    fieldset
    :label="node.props.label ?? undefined"
    :help-text="node.props.instructions ?? undefined"
    :class="{
      [`width-${node.props.width}`]: Boolean(node.props.width),
      hidden: Boolean(node.props.hidden),
      'group-container': true,
    }"
    :hidden="node.props.hidden || undefined"
    :data-form-node="node.uid"
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
      :class="{'group-fields-loading': showLoading}"
    >
      <FormNodeList
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
    :class="{
      [`width-${node.props.width}`]: Boolean(node.props.width),
      hidden: Boolean(node.props.hidden),
      'group-container': true,
    }"
    :hidden="node.props.hidden || undefined"
    :data-form-node="node.uid"
    :aria-busy="showLoading || undefined"
  >
    <legend v-if="!node.props.collapsible && node.props.label">
      {{ node.props.label }}
    </legend>
    <craft-field-group
      :slot="node.props.collapsible ? 'content' : undefined"
      :class="{'group-fields-loading': showLoading}"
    >
      <FormNodeList
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

  .group-fields-loading {
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
