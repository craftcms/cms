<script setup lang="ts">
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/empty/empty';
  import '@craftcms/ui/components/spinner/spinner';
  import {t} from '@craftcms/ui';
  import {useEventListener} from '@vueuse/core';
  import {ref} from 'vue';
  import UiNodeList from './UiNodeList.vue';
  import type {
    UiChange,
    UiControlPayload,
    UiPayload,
    UiValues,
    NestedUiPayload,
  } from './types';
  import {inputName} from './runtime';

  type ContentBlockProps = {
    addLabel: string;
    clearLabel?: string;
    emptyLabel?: string;
  };

  const props = defineProps<{
    control: UiControlPayload<ContentBlockProps>;
    /**
     * Null when the block has no content, and undefined for the beat before the
     * value arrives — see {@link controlValue}. Both mean "nothing to show", so
     * this one tests loosely rather than standing a value in.
     */
    value: UiValues | null | undefined;
    values: UiPayload['values'];
    errors: UiPayload['errors'];
    touchedPaths: Set<string>;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: UiValues | null, kind: 'discrete'): void;
    (event: 'change', change: UiChange): void;
  }>();
  const host = ref<HTMLElement>();

  useEventListener(host, 'input', (event) => {
    if (event.target === host.value) {
      emit(
        'update:value',
        host.value?.querySelector('[data-content-block]') ? {} : null,
        'discrete'
      );
    }
  });

  function nestedChange(change: UiChange, ui: NestedUiPayload): void {
    emit('change', {
      ...change,
      scope: ui.scope,
      refreshable: ui.refreshable && change.refreshable,
    });
  }
</script>

<template>
  <craft-content-block-input
    ref="host"
    :add-label="control.props.addLabel"
    :clear-label="control.props.clearLabel"
    :empty-label="control.props.emptyLabel"
  >
    <input v-if="editable" type="hidden" :name="inputName(control.path)" />
    <craft-empty v-if="value == null" :label="control.props.emptyLabel">
      <craft-button
        v-if="editable"
        type="button"
        icon="plus"
        data-content-block-add
      >
        {{ control.props.addLabel }}
      </craft-button>
    </craft-empty>
    <div v-else class="pane" data-content-block>
      <template v-if="control.uis?.[0]">
        <UiNodeList
          :nodes="control.uis[0].nodes"
          :values="values"
          :errors="errors"
          :touched-paths="touchedPaths"
          :scope="control.uis[0].scope"
          :refreshable="control.uis[0].refreshable"
          @change="nestedChange($event, control.uis[0])"
        />
      </template>
      <craft-spinner v-else :label="t('Loading')" />
      <craft-button
        v-if="editable"
        type="button"
        icon="trash"
        data-content-block-remove
      >
        {{ control.props.clearLabel }}
      </craft-button>
    </div>
  </craft-content-block-input>
</template>
