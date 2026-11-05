<script setup lang="ts">
  /**
   * A nav item's settings, as a slideout panel. See `openNavItemSettings()`.
   *
   * The form comes from the server as a UI payload. Applying has the server
   * check the values, then hands them back to the builder.
   */
  import {computed, ref, shallowRef} from 'vue';
  import {actionClient, isHttpError, t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
  import {applyItemSettings} from '@actions/Settings/CpNavigationController';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSlideout} from '@/common/slideouts';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import type {UiPayload, UiValues} from '@/modules/ui/types';
  import {postedNode, takeNavItemSettingsContext} from '../settings-slideout';
  import type {NavNode} from '../tree';

  const props = defineProps<{contextId: string}>();
  const {node, readOnly, apply, payload} = takeNavItemSettingsContext(
    props.contextId
  );

  const slideout = useSlideout();
  const renderer = ref<InstanceType<typeof UiRenderer> | null>(null);
  const errors = shallowRef<UiPayload['errors']>([]);

  /** Backs the Apply button, and the unsaved-changes prompt. */
  const form = useForm({settings: JSON.stringify(payload.values)});

  const title = computed(() => {
    if (node.kind === 'section') return t('Edit section');
    if (node.type === 'link') return t('Edit link');

    return t('Edit {item}', {item: node.defaultLabel ?? node.label});
  });

  useAppLayout(() => ({
    title: title.value,
    form: readOnly ? null : form,
    submitButtonLabel: t('Apply'),
    defaultFormActions: [],
    onSave: save,
  }));

  function currentValues(): UiValues {
    return renderer.value?.currentValues() ?? {};
  }

  function text(value: unknown): string {
    return typeof value === 'string' ? value.trim() : '';
  }

  async function save(): Promise<void> {
    const values = currentValues();
    errors.value = [];

    try {
      await actionClient.post(applyItemSettings.url(), {
        node: postedNode(node),
        settings: values,
      });
    } catch (cause) {
      const responseErrors = isHttpError<{
        errors?: Record<string, string[]>;
      }>(cause)
        ? cause.response?.data?.errors
        : undefined;

      if (!responseErrors) throw cause;

      errors.value = Object.entries(responseErrors).map(([path, messages]) => ({
        path: path.replace(/^settings\./, '').split('.'),
        messages,
      }));

      return;
    }

    const label = text(values.label);
    const icon = text(values.icon);
    const url = text(values.url);

    apply({
      label: label || node.defaultLabel || node.label,
      ...(node.type === 'link'
        ? {url, href: url, newWindow: Boolean(values.newWindow)}
        : {}),
      ...(node.kind === 'item'
        ? {
            icon: icon || node.defaultIcon,
            iconSvg: icon && icon !== node.icon ? null : node.iconSvg,
          }
        : {}),
      hidden: !values.shown,
      ...(node.source === 'custom'
        ? {
            userCondition: (values.userCondition ??
              null) as NavNode['userCondition'],
          }
        : {}),
    });

    // Before close(): closing drops the panel from the store, and its handler
    // with it.
    slideout?.saved();
    slideout?.close({force: true});
  }
</script>

<template>
  <CpContainer>
    <craft-field-group class="py-lg">
      <UiRenderer
        ref="renderer"
        :payload="payload"
        :errors="errors"
        @change="form.settings = JSON.stringify(currentValues())"
      />
    </craft-field-group>
  </CpContainer>
</template>
