<script setup lang="ts">
  import {actionClient, ButtonVariant, t} from '@craftcms/ui';
  import {
    computed,
    nextTick,
    ref,
    shallowRef,
    useTemplateRef,
    watch,
  } from 'vue';
  import {getElements} from '@/actions/CraftCms/Cms/Http/Controllers/Elements/ElementIndex/ElementIndexController';
  import ElementPickerPlaceholder from '@/modules/element-selector-modal/ElementPickerPlaceholder.vue';
  import type {NestedContentIndexData} from '../nested-elements/nested-elements';
  import EmbeddedElementIndexView from './EmbeddedElementIndexView.vue';

  /**
   * An element index embedded in a screen: it lists the elements matching
   * `criteria`, or those an `EmbeddedElementIndex` token was made for, each
   * linked to its edit page, and can export them. Unless it's read-only, its
   * elements can be selected for element actions and edited inline.
   *
   * `criteria` are sent from the browser, so they decide what the index shows,
   * not what a request is allowed to reach. Pass a `token` when the scope or
   * read-only state has to be enforced on the server.
   */
  const props = defineProps<{
    elementType: string;
    /**
     * The element query criteria to list. They come from the browser, so they
     * shape the list but don't restrict access: element actions and inline
     * editing still check each element's own permissions.
     */
    criteria?: Record<string, unknown>;
    /**
     * The token of a PHP `EmbeddedElementIndex`, used in place of `criteria`.
     * Its criteria and read-only state are enforced on the server.
     */
    token?: string;
    /** The first page, when the screen already has it. */
    initial?: NestedContentIndexData;
    /** Keeps this index's view settings apart from other indexes of the same element type. */
    storageKey?: string;
    /**
     * Only lists and exports the elements, without element actions or inline
     * editing. Without a token this only hides those controls; a read-only
     * token is enforced on the server.
     */
    readOnly?: boolean;
  }>();

  const params = computed<Record<string, unknown>>(() =>
    props.token
      ? {embeddedIndexToken: props.token}
      : {embeddedCriteria: {...props.criteria}}
  );
  const payload = shallowRef<NestedContentIndexData | null>(
    props.initial ?? null
  );
  const failed = ref(false);
  const generation = ref(0);
  const root = useTemplateRef<HTMLElement>('root');
  let latestRequest = 0;

  function focusIsInside(): boolean {
    return Boolean(root.value?.contains(document.activeElement));
  }

  /** Whether focus is on nothing in particular, or still in this index. */
  function focusIsFree(): boolean {
    const active = document.activeElement;

    return !active || active === document.body || focusIsInside();
  }

  /**
   * Puts focus back after the index's content was replaced, but only if the
   * user hadn't moved on in the meantime.
   */
  async function restoreFocus(selector: string): Promise<void> {
    await nextTick();

    if (focusIsFree()) {
      root.value?.querySelector<HTMLElement>(selector)?.focus();
    }
  }

  const focusableControl =
    'input:not([disabled]), button:not([disabled]), a[href]';

  function show(next: NestedContentIndexData, hadFocus: boolean): void {
    payload.value = next;
    generation.value++;

    if (hadFocus) {
      void restoreFocus(focusableControl);
    }
  }

  async function load(retried = false): Promise<void> {
    const request = ++latestRequest;
    const hadFocus = retried || focusIsInside();
    payload.value = null;
    failed.value = false;

    try {
      const {data} = await actionClient.post<NestedContentIndexData>(
        getElements.url(),
        {
          elementType: props.elementType,
          context: 'embeddedIndex',
          source: '__IMP__',
          ...params.value,
        }
      );

      if (request === latestRequest) {
        show(data, hadFocus);
      }
    } catch {
      if (request === latestRequest) {
        showFailure(hadFocus);
      }
    }
  }

  /**
   * Moves focus to the retry button when focus was in this index, or on the
   * retry just clicked, so a background failure doesn't interrupt the user
   * elsewhere on the screen.
   */
  function showFailure(hadFocus = focusIsInside()): void {
    failed.value = true;

    if (hadFocus) {
      void restoreFocus('craft-button');
    }
  }

  watch(
    [
      () => JSON.stringify([props.elementType, params.value]),
      () => props.initial,
    ],
    ([, initial], previous) => {
      if (initial && initial !== previous?.[1]) {
        latestRequest++;
        failed.value = false;
        show(initial, focusIsInside());

        return;
      }

      void load();
    },
    {immediate: !props.initial}
  );
</script>

<template>
  <div ref="root">
    <div v-if="failed" role="alert" class="flex items-center gap-md">
      <span>{{ t('The list couldn’t be loaded.') }}</span>
      <craft-button
        type="button"
        :variant="ButtonVariant.Plain"
        @click="load(true)"
        >{{ t('Try again') }}</craft-button
      >
    </div>
    <EmbeddedElementIndexView
      v-else-if="payload"
      :key="`${generation}-${readOnly}`"
      :element-type="elementType"
      :initial="payload"
      :params="params"
      :read-only="readOnly"
      :storage-key="`embeddedindex.${storageKey ?? elementType}`"
      @error="showFailure()"
    />
    <ElementPickerPlaceholder v-else :element-type="elementType" />
  </div>
</template>
