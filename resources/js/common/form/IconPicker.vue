<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import Modal from '@/common/components/Modal.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import Pane from '@/common/components/Pane.vue';
  import {
    computed,
    onMounted,
    ref,
    watch,
    type ComponentPublicInstance,
  } from 'vue';
  import {watchDebounced} from '@vueuse/core';
  import {useHttp} from '@inertiajs/vue3';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import IconController from '@actions/IconController';
  import Empty from '@/common/components/Empty.vue';

  type PickerOptionsResponse = {listHtml: string};

  const model = defineModel<string>();
  defineProps<{
    label?: string;
    name?: string;
    error?: string;
  }>();

  const query = ref<string>('');
  const modalActive = ref<boolean>(false);
  const iconHtml = ref<string | null>(null);
  const chooseButton = ref<HTMLElement | null>(null);
  const searchInput = ref<ComponentPublicInstance | null>(null);

  const http = useHttp<Record<string, never>, PickerOptionsResponse>();
  const {announce} = useAnnouncer();

  watch(
    () => http.processing,
    (processing) => {
      if (processing) {
        announce(t('Loading ...'));
      }
    }
  );

  function loadIcons() {
    http.get(
      IconController.pickerOptions(undefined, {query: {search: query.value}})
        .url,
      {
        onSuccess: (response) => {
          iconHtml.value = response.listHtml;
        },
        onError: (error) => {
          console.log({error});
        },
      }
    );
  }

  function openModal() {
    loadIcons();
    modalActive.value = true;
  }

  function focusSearch() {
    searchInput.value?.$el?.focus();
  }

  watchDebounced(
    query,
    () => {
      if (query.value !== '' && query.value.length < 3) {
        return;
      }

      loadIcons();
    },
    {debounce: 300}
  );

  const buttonLabel = computed(() => (model.value ? t('Change') : t('Choose')));

  function handleClick(event: MouseEvent) {
    const target = event.target as HTMLElement;
    if (!target) {
      return;
    }

    let button;
    if (target?.nodeName === 'BUTTON') {
      button = target;
    } else {
      button = target.closest('button');
      if (!button) {
        console.log('No button found, I am out of here');
        return;
      }
    }

    // @TODO probably don't use title for this
    modalActive.value = false;
    model.value = button.getAttribute('title') ?? '';
    chooseButton.value?.focus();
  }
</script>

<template>
  <craft-input
    :label="label"
    :name="name"
    :has-feedback-for="error ? 'error' : ''"
    hidden-input
    .modelValue="model"
  >
    <div class="flex gap-2 items-center" slot="before">
      <div class="icon-preview">
        <craft-icon
          :name="model"
          :label="model"
          style="font-size: calc(18rem / 16)"
        ></craft-icon>
      </div>
      <div class="flex gap-1 items-center">
        <craft-button
          type="button"
          size="small"
          ref="chooseButton"
          @click.prevent="openModal"
          >{{ buttonLabel }}</craft-button
        >
        <craft-button
          v-if="model"
          type="button"
          size="small"
          @click.prevent="model = ''"
          >{{ t('Remove') }}</craft-button
        >
      </div>
    </div>
  </craft-input>

  <Modal
    :is-active="modalActive"
    width="xl"
    @close="modalActive = false"
    @opened="focusSearch"
  >
    <Pane :title="t('Select Icon')">
      <form role="search" @submit.prevent="loadIcons()">
        <CraftInput :label="t('Search')" v-model="query" ref="searchInput">
          <div slot="suffix" class="flex self-center w-[1em] h-[1em]">
            <craft-spinner
              style="--size: 1em"
              :visible="http.processing && iconHtml !== null"
            ></craft-spinner>
          </div>
        </CraftInput>
      </form>
      <div class="mt-3">
        <!-- This only shows on the initial load -->
        <template v-if="http.processing && iconHtml === null">
          <div class="flex justify-center p-4">
            <craft-spinner></craft-spinner>
          </div>
        </template>

        <template v-else-if="!iconHtml?.length">
          <Empty :label="t('No icons found matching “{query}”', {query})" />
        </template>

        <template v-else>
          <ul class="icon-grid" v-html="iconHtml" @click="handleClick"></ul>
        </template>
      </div>
    </Pane>
  </Modal>
</template>

<style scoped lang="scss">
  .icon-preview {
    aspect-ratio: 1;
    width: calc(34rem / 16);
    background-color: var(--c-color-fill-quiet);
    border: 1px solid var(--c-color-border-quiet);
    color: var(--c-color-on-quiet);
    border-radius: var(--c-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(34px, 1fr));
    gap: var(--c-spacing-sm);
  }
</style>
