<script setup lang="ts">
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';
  import {computed, ref} from 'vue';
  import {useHelpers} from '@/composables/useCraftData';
  import axios from 'axios';
  import {t} from '@craftcms/cp';
  import type {CompleteWidget} from '@/types';

  const emit = defineEmits<{
    (e: 'update', widget: any): void;
    (e: 'delete', widgetId: number): void;
  }>();

  const props = withDefaults(defineProps<CompleteWidget>(), {
    view: 'default',
    mode: 'view',
    new: false,
    settingsNamespace: '',
  });

  const {getCpUrl, getActionUrl} = useHelpers();
  const state = ref<'idle' | 'loading' | 'deleting' | 'error'>('idle');

  function setView(view: 'default' | 'settings') {
    emit('update', {
      ...props,
      view,
    });
  }

  function toggleView() {
    if (props.view === 'default') {
      setView('settings');
    } else {
      setView('default');
    }
  }

  /**
   * Previously creating & updating widgets was done on the queue
   * @param event
   */
  async function handleSubmit(event: Event) {
    if (state.value === 'loading') {
      return;
    }

    state.value = 'loading';
    const form = event.target as HTMLFormElement;
    const formData = new FormData(form);

    try {
      const {data} = await axios.post(form.action, formData);
      state.value = 'idle';
      emit('update', {
        ...data.info,
        view: 'default',
      });
    } catch (error) {
      state.value = 'error';
      // @TODO handle errors
      console.error(error);
    }
  }

  async function deleteWidget() {
    if (['deleting', 'loading'].includes(state.value)) {
      return;
    }

    if (
      !confirm(
        t('app', 'Are you sure you want to delete “{name}”?', {
          name: props.name,
        })
      )
    ) {
      return;
    }

    state.value = 'deleting';
    try {
      await axios.post(getCpUrl(`widgets/${props.id}/delete`), {
        id: props.id,
      });
      emit('delete', props.id);
      state.value = 'idle';
      // @TODO display success message
      // Craft.cp.displaySuccess(t('app', '“{name}” deleted.', {name: widget.getLabel()}));
      // @TODO allow undo in the toast message
    } catch (error) {
      state.value = 'error';
      // @TODO handle errors
      console.error(error);
      // Craft.cp.displayError(t('app', 'Couldn’t delete “{name}”.', {
      //   name: props.name,
      // }))
    }
  }

  const action = computed(() =>
    props.new ? getCpUrl('widgets') : getCpUrl(`widgets/${props.id}`)
  );

  // To test legacy routes
  // const action = computed(() => {
  //   return props.new
  //     ? getActionUrl('dashboard/create-widget')
  //     : getActionUrl('dashboard/save-widget-settings');
  // });
</script>

<template>
  <craft-card :label="title">
    <div slot="actions" v-if="settingsHtml">
      <craft-button
        icon
        size="small"
        appearance="plain"
        :class="{
          'is-active': view === 'settings',
        }"
        @click="toggleView"
      >
        <craft-icon name="gear"></craft-icon>
      </craft-button>
    </div>

    <DynamicHtmlRenderer
      :html="bodyHtml"
      v-if="view === 'default'"
    ></DynamicHtmlRenderer>

    <template v-if="settingsHtml && view === 'settings'">
      <form @submit.prevent="handleSubmit" :action="action">
        <input type="hidden" name="widgetId" :value="id" />
        <input type="hidden" name="type" :value="type" />
        <input
          type="hidden"
          name="settingsNamespace"
          :value="settingsNamespace"
        />

        <div class="tw:grid tw:gap-3">
          <DynamicHtmlRenderer :html="settingsHtml"></DynamicHtmlRenderer>
        </div>

        <div
          class="tw:flex tw:gap-2 tw:mt-6 tw:pt-3 tw:border-t tw:border-neutral-200"
        >
          <craft-button
            :loading="state === 'loading'"
            variant="primary"
            type="submit"
            >{{ t('app', 'Save') }}</craft-button
          >
          <craft-button type="reset" @click="setView('default')">{{
            t('app', 'Cancel')
          }}</craft-button>
          <div class="tw:ml-auto"></div>
          <craft-button
            type="button"
            appearance="plain"
            variant="danger"
            :loading="state === 'deleting'"
            @click="deleteWidget"
            >{{ t('app', 'Delete') }}</craft-button
          >
        </div>
      </form>
    </template>
  </craft-card>
</template>

<style scoped lang="scss">
  craft-button.is-active {
    background-color: var(--c-color-neutral-bg-emphasis);
    color: var(--c-color-neutral-on-emphasis);
  }
</style>
