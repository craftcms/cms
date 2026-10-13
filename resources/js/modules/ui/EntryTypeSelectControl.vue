<script setup lang="ts">
  import {useEventListener} from '@vueuse/core';
  import {actionClient, appendBodyHtml, appendHeadHtml, t} from '@craftcms/ui';
  import type {DefineChipActionsEventDetail} from '@/modules/component-select';
  import {editEntryTypeOverrides} from '@/modules/grouped-entry-type-manager/entry-type-override-settings';
  import type {UiControlPayload, UiValue} from './types';
  import {inputName} from './runtime';
  import {useServerRenderedControl} from './useServerRenderedControl';

  type EntryTypeSelectProps = {
    allowOverrides?: boolean;
    create?: boolean;
  };

  const props = defineProps<{
    control: UiControlPayload<EntryTypeSelectProps>;
    value: UiValue[];
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: UiValue[], kind: 'discrete'): void;
  }>();
  let headHtml = '';
  let bodyHtml = '';
  const {host, html} = useServerRenderedControl({
    value: () => props.value,
    dependencies: [() => props.control.props, () => props.editable],
    async render() {
      const response = await actionClient.post<{
        html: string;
        headHtml: string;
        bodyHtml: string;
      }>('entry-types/render-select', {
        value: props.value ?? [],
        allowOverrides: Boolean(props.control.props.allowOverrides),
        create: Boolean(props.control.props.create),
        name: inputName(props.control.path),
        disabled: !props.editable,
      });

      headHtml = response.data.headHtml;
      bodyHtml = response.data.bodyHtml;

      return response.data.html;
    },
    async afterRender() {
      await appendHeadHtml(headHtml);
      await appendBodyHtml(bodyHtml);
    },
    readValue(host) {
      if (!props.editable) {
        return;
      }

      const name = `${inputName(props.control.path)}[]`;

      return [...host.querySelectorAll<HTMLInputElement>('input[name]')]
        .filter(
          (input) => input.name === name && !input.closest('li[data-removing]')
        )
        .map((input) => input.value)
        .filter(Boolean)
        .map((value): UiValue => {
          if (!props.control.props.allowOverrides) {
            return Number(value);
          }

          // SAFETY: With overrides allowed, the EntryTypeSelect component renders each
          // chip's hidden value as an `{id, name?, handle?, description?}` JSON object.
          return JSON.parse(value) as UiValue;
        });
    },
    update: (value) => emit('update:value', value, 'discrete'),
  });

  // The chip's built-in "Entry type settings" item edits the shared entry
  // type globally; with overrides allowed, swap it for a Settings action that
  // only overrides the entry type for this selection.
  useEventListener(
    host,
    'define-chip-actions',
    (event: CustomEvent<DefineChipActionsEventDetail>) => {
      if (!props.control.props.allowOverrides || !props.editable) {
        return;
      }

      const {chip, actions} = event.detail;
      chip.querySelector('[data-edit-action]')?.setAttribute('hidden', '');
      actions.push({
        icon: 'gear',
        label: t('Settings'),
        onActivate: () => void editEntryTypeOverrides(chip),
      });
    }
  );
</script>

<template>
  <div ref="host" v-html="html"></div>
</template>
