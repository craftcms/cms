<script setup lang="ts">
  defineOptions({inheritAttrs: false});

  import {t} from '@craftcms/ui';
  import Modal from '@/common/components/Modal.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import {computed, nextTick, ref, watch} from 'vue';
  import {watchDebounced} from '@vueuse/core';
  import {useHttp} from '@inertiajs/vue3';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {useAsyncIcon} from '../composables/useAsyncIcon';
  import IconController from '@actions/IconController';

  /** An icon the picker offers: its name, and the SVG it's drawn with. */
  type IconOption = {name: string; svg: string};
  type PickerOptionsResponse = {icons: IconOption[]};

  const model = defineModel<string>();
  const props = withDefaults(
    defineProps<{
      label?: string;
      name?: string;
      error?: string;
      freeOnly?: boolean;
      disabled?: boolean;
      // Used by the legacy `<craft-icon-picker>` mount bridge: `id` lets a
      // wrapping `craft-field` label associate with the control, and
      // labelledBy/describedBy cover the label-less table-cell case.
      id?: string;
      labelledBy?: string;
      describedBy?: string;
    }>(),
    {freeOnly: false}
  );

  const contentLang = document.documentElement.lang.startsWith('en')
    ? undefined
    : 'en';

  const query = ref<string>('');
  const modalActive = ref<boolean>(false);
  /** The icons on offer, or null before the first load. */
  const icons = ref<IconOption[] | null>(null);
  const chooseButton = ref<HTMLElement | null>(null);
  const searchForm = ref<HTMLFormElement | null>(null);

  const {html: previewHtml, state: previewState} = useAsyncIcon(model);

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
      IconController.pickerOptions(undefined, {
        query: {search: query.value, freeOnly: props.freeOnly},
      }).url,
      {
        onSuccess: (response) => {
          icons.value = response.icons;

          announce(
            `${t('Loading complete')} - ${t(
              '{num, number} {num, plural, =1{result} other{results}}',
              {num: response.icons.length}
            )}`
          );
        },
      }
    );
  }

  function openModal() {
    if (icons.value === null) {
      loadIcons();
    }
    modalActive.value = true;
  }

  function focusSearch() {
    searchForm.value?.querySelector<HTMLElement>('input')?.focus();
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

  function choose(name: string) {
    modalActive.value = false;
    model.value = name;
    chooseButton.value?.focus();
  }

  function removeIcon() {
    model.value = '';
    nextTick(() => {
      chooseButton.value?.focus();
    });
  }
</script>

<template>
  <craft-input
    v-bind="$attrs"
    :id="id"
    :label="label"
    :name="name"
    :aria-labelledby="labelledBy"
    :aria-describedby="describedBy"
    :has-feedback-for="error ? 'error' : ''"
    hidden-input
    .modelValue="model"
    :disabled="disabled"
  >
    <div class="flex gap-2 items-center" slot="before">
      <div class="icon-preview" :lang="contentLang">
        <craft-spinner
          v-if="previewState === 'fetching'"
          style="--size: 1em"
          visible
        ></craft-spinner>
        <div v-else class="contents" v-html="previewHtml"></div>
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
          @click.prevent="removeIcon"
          >{{ t('Remove') }}</craft-button
        >
      </div>
    </div>
  </craft-input>

  <!-- Teleported: the picker usually sits inside a web component (a field,
    say) that only renders the slots it knows, so a modal left beside the
    input would never be drawn — and inside another modal it would inherit
    that modal's form and stacking context. -->
  <Teleport to="body">
    <Modal
      :is-active="modalActive"
      width="xl"
      height="calc(550rem / 16)"
      @close="modalActive = false"
      @opened="focusSearch"
    >
      <craft-pane class="icon-picker-pane h-full">
        <form
          ref="searchForm"
          slot="header"
          role="search"
          @submit.prevent="loadIcons()"
          class="sticky top-0 pt-4 px-4 pb-2 bg-white"
        >
          <CraftInput :label="t('Search')" v-model="query">
            <div slot="suffix" class="flex self-center w-[1em] h-[1em]">
              <craft-spinner
                style="--size: 1em"
                :visible="http.processing && icons !== null"
              ></craft-spinner>
            </div>
          </CraftInput>
        </form>
        <div>
          <!-- This only shows on the initial load -->
          <template v-if="http.processing && icons === null">
            <div class="flex justify-center p-4">
              <craft-spinner></craft-spinner>
            </div>
          </template>

          <template v-else-if="!icons?.length">
            <craft-empty
              :label="t('No icons found matching “{query}”', {query})"
            ></craft-empty>
          </template>

          <template v-else>
            <ul class="icon-grid" :lang="contentLang">
              <li v-for="icon in icons" :key="icon.name">
                <button
                  type="button"
                  class="icon-picker--icon"
                  :title="icon.name"
                  :aria-label="icon.name"
                  @click="choose(icon.name)"
                  v-html="icon.svg"
                ></button>
              </li>
            </ul>
          </template>
        </div>
      </craft-pane>
    </Modal>
  </Teleport>
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

  /* The pane fills the modal and clips what overflows it, so the results
     scroll within its body, under the search box, rather than the list running
     out of reach below the modal. */
  .icon-picker-pane::part(base) {
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .icon-picker-pane::part(body) {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
  }

  .icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(34px, 1fr));
    gap: var(--c-spacing-sm);
  }

  .icon-picker--icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: var(--ui-control-height);
    height: var(--ui-control-height);
    border-radius: var(--c-radius-sm);
    background-color: var(--c-color-fill-quiet);
    border: 1px solid var(--c-color-border-quiet);
    color: var(--c-color-on-quiet);
    cursor: pointer;

    &:hover {
      background-color: color-mix(
        in oklab,
        var(--c-color-fill-quiet, var(--c-button-default-fill)),
        var(--c-color-mix-hover)
      );
      color: var(--c-color-on-quiet);
    }
  }

  :deep(svg) {
    width: calc(20rem / 16);
    height: calc(20rem / 16);
  }
</style>
