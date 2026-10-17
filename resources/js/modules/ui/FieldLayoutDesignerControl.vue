<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {actionClient, t} from '@craftcms/ui';
  import {onBeforeUnmount, watch} from 'vue';
  import type {UiControlPayload, UiPayload, UiValue, UiValues} from './types';
  import {inputName} from './runtime';
  import {registerOutlets} from './outlets';
  import {useServerRenderedControl} from './useServerRenderedControl';

  type FieldLayoutDesignerProps = {
    elementType: string;
    customizableTabs: boolean;
    withGeneratedFields: boolean;
    withCardViewDesigner: boolean;
  };

  const props = defineProps<{
    control: UiControlPayload<FieldLayoutDesignerProps>;
    value: UiValues;
    editable: boolean;
    errors?: UiPayload['errors'];
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: UiValues, kind: 'discrete'): void;
  }>();
  const {host, html} = useServerRenderedControl({
    value: () => props.value,
    dependencies: [() => props.control.props, () => props.editable],
    async render() {
      const response = await actionClient.post<{html: string}>(
        'fields/render-field-layout-designer',
        {
          value: props.value,
          elementType: props.control.props.elementType,
          name: inputName(props.control.path),
          disabled: !props.editable,
          customizableTabs: props.control.props.customizableTabs,
          withGeneratedFields: props.control.props.withGeneratedFields,
          withCardViewDesigner: props.control.props.withCardViewDesigner,
        }
      );

      return response.data.html;
    },
    readValue(host) {
      const input = host.querySelector<HTMLInputElement>('[data-config-input]');

      if (!input) {
        return undefined;
      }

      // SAFETY: The config input is JSON rendered by the field-layout designer.
      const value = JSON.parse(input.value) as UiValues;
      const generatedFields = host.querySelector<
        HTMLElement & {serialize(): UiValue[]}
      >('craft-generated-fields-table');

      return generatedFields
        ? {...value, generatedFields: generatedFields.serialize()}
        : value;
    },
    update: (value) => emit('update:value', value, 'discrete'),
    afterRender(root) {
      syncTabErrors(root);
      // The Appearance tab's outlet, for the owner's own display settings.
      releaseOutlets?.();
      releaseOutlets = registerOutlets(root);
    },
  });
  let releaseOutlets: (() => void) | undefined;
  onBeforeUnmount(() => releaseOutlets?.());

  /**
   * The top-level settings an error falls under for each of the designer's
   * tabs, relative to the control's own path.
   */
  const tabErrorKeys: Record<string, string[]> = {
    content: ['customFields', 'tabs', 'generatedFields'],
    display: ['cardView', 'thumbFieldKey'],
  };

  /** Flags the designer's Content and Appearance tabs that have errors. */
  function syncTabErrors(root: HTMLElement): void {
    const base = props.control.path;
    // The designer owns every error raised within it, so where each one
    // belongs is in the path it was raised at.
    const keys = (props.errors ?? [])
      .map((error) =>
        error.messages.length ? (error.source ?? error.path) : []
      )
      .filter(
        (path) =>
          path.length > base.length &&
          base.every((segment, i) => path[i] === segment)
      )
      .map((path) => path[base.length]!);

    for (const [tabName, errorKeys] of Object.entries(tabErrorKeys)) {
      const tab = root.querySelector(`craft-tab[data-fld-tab="${tabName}"]`);
      if (!tab) {
        continue;
      }

      const hasErrors = keys.some((key) => errorKeys.includes(key));
      let icon = tab.querySelector(':scope > [data-fld-tab-error]');
      if (hasErrors && !icon) {
        icon = document.createElement('craft-icon');
        icon.setAttribute('name', 'circle-exclamation');
        icon.setAttribute('slot', 'suffix');
        icon.setAttribute('label', t('Errors'));
        icon.setAttribute('data-fld-tab-error', '');
        tab.append(icon);
      } else if (!hasErrors) {
        icon?.remove();
      }
    }
  }

  watch(
    () => props.errors,
    () => {
      if (host.value) {
        syncTabErrors(host.value);
      }
    },
    {deep: true}
  );
</script>

<template>
  <div ref="host">
    <DynamicHtmlRenderer :html="html" />
  </div>
</template>
