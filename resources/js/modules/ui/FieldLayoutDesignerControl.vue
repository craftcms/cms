<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {actionClient, t} from '@craftcms/ui';
  import {useEventListener} from '@vueuse/core';
  import {onBeforeUnmount, shallowRef, watch} from 'vue';
  import type {UiControlPayload, UiPayload, UiValue, UiValues} from './types';
  import {inputName} from './runtime';
  import {useOutlets} from './outlets';
  import {useServerRenderedControl} from './useServerRenderedControl';
  import ServerRenderedControlFrame from './ServerRenderedControlFrame.vue';

  type FieldLayoutDesignerProps = {
    elementType: string;
    customizableTabs: boolean;
    withGeneratedFields: boolean;
    withCardViewDesigner: boolean;
    /** Where the card view designer moves to, in a tab of its own. */
    cardViewDesignerOutlet?: string;
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
  const outlets = useOutlets();
  // The card view designer holds the Appearance tab's outlet. Groups bound
  // for it wait for the first render rather than render in place and jump.
  const releasePendingOutlets = outlets.expect(
    props.control.props.withCardViewDesigner
      ? ['field-layout-designer.appearance']
      : []
  );
  const {host, html, status, retry, events, scheduleRead} =
    useServerRenderedControl({
      value: () => props.value,
      dependencies: [() => props.control.props, () => props.editable],
      async render() {
        const response = await actionClient
          .post<{html: string}>('fields/render-field-layout-designer', {
            value: props.value,
            elementType: props.control.props.elementType,
            name: inputName(props.control.path),
            disabled: !props.editable,
            customizableTabs: props.control.props.customizableTabs,
            withGeneratedFields: props.control.props.withGeneratedFields,
            withCardViewDesigner: props.control.props.withCardViewDesigner,
            cardViewDesignerOutlet:
              props.control.props.cardViewDesignerOutlet ?? null,
          })
          .catch((error: unknown) => {
            releasePendingOutlets();
            throw error;
          });

        return response.data.html;
      },
      readValue(host) {
        const input = host.querySelector<HTMLInputElement>(
          '[data-config-input]'
        );

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
        releaseOutlets = outlets.registerAll(root);
        releasePendingOutlets();
        // The designer boots as its element connects, so it has already found
        // its card view designer, which can move out now. The one from an
        // earlier render was moved out of this control, so it goes too.
        cardViewDesigner.value?.remove();
        cardViewDesigner.value = props.control.props.cardViewDesignerOutlet
          ? root.querySelector<HTMLElement>('.card-view-designer')
          : null;
      },
    });
  let releaseOutlets: (() => void) | undefined;
  onBeforeUnmount(() => {
    releasePendingOutlets();
    releaseOutlets?.();
    cardViewDesigner.value?.remove();
  });

  /**
   * The card view designer, when it moves to the outlet the control names.
   * It stays hidden here until the outlet exists, as it belongs in another
   * tab, and changes made in it still update the control's value.
   */
  const cardViewDesigner = shallowRef<HTMLElement | null>(null);
  const cardViewDesignerOutlet = outlets.outlet(
    () => props.control.props.cardViewDesignerOutlet
  );
  watch([cardViewDesigner, cardViewDesignerOutlet], ([element, outlet]) => {
    if (!element) {
      return;
    }

    element.hidden = !outlet;
    if (outlet && element.parentElement !== outlet) {
      outlet.append(element);
    }
  });
  useEventListener(
    () => (cardViewDesignerOutlet.value ? cardViewDesigner.value : null),
    events,
    scheduleRead
  );

  /**
   * The top-level settings an error falls under for each of the designer's
   * tabs, relative to the control's own path.
   */
  const tabErrorKeys: Record<string, string[]> = {
    content: ['customFields', 'tabs', 'generatedFields'],
    appearance: ['cardView', 'thumbSources'],
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
    }
  );
</script>

<template>
  <ServerRenderedControlFrame
    :status="status"
    :has-html="Boolean(html)"
    block-size="24rem"
    @retry="retry"
  >
    <div ref="host">
      <DynamicHtmlRenderer :html="html" />
    </div>
  </ServerRenderedControlFrame>
</template>
