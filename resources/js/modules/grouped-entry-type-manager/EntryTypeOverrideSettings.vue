<script setup lang="ts">
  /**
   * A chip's per-field entry type overrides, as a slideout panel. The form is
   * server-rendered HTML, so it's serialized on Apply rather than bound.
   */
  import {useForm} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {serializeFormInputs} from '@craftcms/ui/utilities/dom';
  import CpContainer from '@/common/components/CpContainer.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSlideout} from '@/common/slideouts';
  import {takeOverrideSettingsContext} from './entry-type-override-settings';
  import {addErrorsToField} from '@/common/craft/field-errors';
  import {showError} from '@/modules/messages/messages';

  declare const Craft: any;

  const props = defineProps<{
    contextId: string;
    title: string;
  }>();
  const context = takeOverrideSettingsContext(props.contextId);

  const slideout = useSlideout();
  let container: HTMLElement | null = null;

  /** Backs the shell's Apply button and its unsaved-changes check. */
  const form = useForm({settings: ''});

  useAppLayout(() => ({
    title: props.title,
    form,
    submitButtonLabel: t('Apply'),
    defaultFormActions: [],
    onSave: apply,
  }));

  function serialize(): string {
    return container ? serializeFormInputs(container) : '';
  }

  function onReady(element: HTMLElement): void {
    container = element;
    form.defaults({settings: serialize()});
    form.reset();

    element.addEventListener('input', onChange);
    element.addEventListener('change', onChange);
    element.querySelector<HTMLElement>('.text')?.focus();
  }

  function onChange(): void {
    form.settings = serialize();
  }

  function clearErrors(): void {
    container
      ?.querySelectorAll<HTMLElement>('.field.has-errors')
      .forEach((field) => {
        field.classList.remove('has-errors');
        field
          .querySelector(':scope > .input')
          ?.classList.remove('errors', 'prevalidate');
        field.querySelector(':scope > ul.errors')?.remove();
      });
  }

  async function apply(): Promise<void> {
    clearErrors();
    form.processing = true;

    try {
      await context.apply(serialize());
    } catch (error: any) {
      const data = error?.response?.data;

      Object.entries(data?.errors ?? {}).forEach(([name, fieldErrors]) => {
        const field = container?.querySelector(`[data-error-key="${name}"]`);
        if (field) {
          addErrorsToField(field, fieldErrors as string[]);
        }
      });

      showError(data?.message);

      return;
    } finally {
      form.processing = false;
    }

    slideout?.close({force: true});
  }
</script>

<template>
  <CpContainer>
    <HtmlFragmentRenderer
      :fragment="context.fragment"
      as="craft-field-group"
      class="py-lg"
      @ready="onReady"
    />
  </CpContainer>
</template>
