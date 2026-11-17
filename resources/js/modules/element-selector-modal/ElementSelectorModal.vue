<script setup lang="ts">
  import {computed, onMounted, onUnmounted, ref} from 'vue';
  import {t, type ElementSelectorController} from '@craftcms/ui';
  import '@craftcms/ui/components/element-selector-modal/element-selector-modal';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import AssetUploadButton from '@/pages/assets/AssetUploadButton.vue';
  import FolderActionButtons from '@/modules/assets/FolderActionButtons.vue';
  import type {ActionItemButton} from '@/common/types';
  import ElementPicker from './ElementPicker.vue';
  import ElementPickerPlaceholder from './ElementPickerPlaceholder.vue';
  import NewEntryButton from './NewEntryButton.vue';
  import type {Source, SourceItem} from '@/modules/elements/types/sources';
  import type {SelectedElement} from './useModalElementIndex';
  import {useElementSelectorController} from './useElementSelectorController';

  const props = defineProps<{
    controller: ElementSelectorController;
    /** What takes focus once the modal has closed. See `CraftDialog.returnFocus`. */
    returnFocus?: () => HTMLElement | null;
    /** See `ElementSelectorModalPresentationOptions.onHandoff`. */
    onHandoff?: (settled: Promise<void>) => void;
  }>();

  /** The slice of `AssetSelectorController` this component needs. */
  interface TransformCapable {
    readonly transforms: readonly {handle: string; name: string}[];
    selectWithTransform(handle: string): Promise<void>;
  }

  /**
   * Duck-typed rather than `instanceof AssetSelectorController`.
   *
   * The controller may have been built from a different copy of the package
   * (a plugin bundling its own), where `instanceof` quietly fails. The shape is
   * the contract.
   */
  function asTransformCapable(candidate: unknown): TransformCapable | null {
    const c = candidate as Partial<TransformCapable> | null;

    return typeof c?.selectWithTransform === 'function' &&
      Array.isArray(c.transforms) &&
      c.transforms.length > 0
      ? (c as TransformCapable)
      : null;
  }

  const transformable = computed(() => asTransformCapable(props.controller));
  const uploadKinds = computed(() => {
    const kind = props.controller.options.criteria?.kind;

    return kind === undefined ? undefined : ([kind].flat() as string[]);
  });

  /**
   * No emits: the controller is the event bus.
   *
   * Anything that wants to know about a selection listens to the controller (or
   * passes `onSelect`), which is also what the web component and the imperative
   * openers do. Re-emitting here would give one event two sources.
   */
  const {indexBody, disabledElementIds, selection} =
    useElementSelectorController(props.controller);

  /**
   * The disabled state lives on the items, not the invoker.
   *
   * `ActionMenu` renders its invoker under `v-once` — mandatory, because
   * `craft-action-menu` relocates its own light DOM and Vue re-patching it
   * throws. A reactive `disabled` on the invoker would therefore never update,
   * so the reactivity goes through the `actions` prop instead, which the web
   * component consumes as data rather than as Vue-managed DOM.
   */
  const transformActions = computed(() =>
    (transformable.value?.transforms ?? []).map((transform) => ({
      label: transform.name,
      disabled: selection.value.length === 0,
      onClick: () =>
        void transformable.value?.selectWithTransform(transform.handle),
    }))
  );

  const index = ref<InstanceType<typeof ElementPicker> | null>(null);

  /**
   * The source the index is showing, which is what an upload targets.
   *
   * Asset sources are folders — one per volume and per subfolder — so the
   * selected source *is* the current folder, and its data bag carries what the
   * uploader needs. No element type is special-cased here: a source that can't
   * be uploaded into simply never carries these keys, which is what keeps the
   * button off entry and category indexes.
   */
  const source = ref<SourceItem | null>(null);

  /** Every source the index offers, and the site it's showing. */
  const sources = ref<Source[]>([]);
  const siteId = ref<number | null>(null);

  /** The buttons plugins add for the folder on screen. */
  const folderActions = ref<ActionItemButton[]>([]);

  /**
   * Entries can be created from their selector, as Craft 5's `EntryIndex`
   * offered — in the sections the index shows that the user can publish in.
   * Not with a selection condition: the modal closes while the entry is
   * edited, so nothing could check the saved entry against the condition
   * before choosing it.
   */
  const createsEntries = computed(
    () =>
      props.controller.elementType ===
        'CraftCms\\Cms\\Entry\\Elements\\Entry' &&
      !props.controller.options.condition
  );

  const uploadTarget = computed(() => {
    const data = source.value?.data as
      | Record<string, boolean | number | string>
      | undefined;

    if (data?.['can-upload'] !== true) {
      return null;
    }

    const folderId = data['folder-id'];
    return typeof folderId === 'number' ? {folderId} : null;
  });

  onMounted(() => {
    props.controller.attachIndex({
      clearSelection: () => index.value?.clearSelection(),
    });
  });

  onUnmounted(() => props.controller.detachIndex());
</script>

<template>
  <craft-element-selector-modal
    :controller="controller"
    .returnFocus="returnFocus"
  >
    <!--
      The index talks to the controller, never to the chrome around it — it emits
      what happened and the controller decides what that means. That is what lets
      the same index sit inside the web component here and inside a plugin's own
      presentation elsewhere.
    -->
    <ElementPicker
      v-if="indexBody"
      ref="index"
      :action="controller.options.bodyAction"
      :initial="indexBody.props as any"
      :params="controller.indexParams()"
      :disabled-element-ids="disabledElementIds"
      @selection-change="
        (elements: SelectedElement[]) => controller.setSelection(elements)
      "
      @choose="() => controller.submit()"
      @source-change="(next: SourceItem | null) => (source = next)"
      @sources-change="(next: Source[]) => (sources = next)"
      @site-change="(next: number | null) => (siteId = next)"
      @folder-actions-change="
        (next: ActionItemButton[]) => (folderActions = next)
      "
    />
    <ElementPickerPlaceholder
      v-else
      slot="loading"
      :element-type="controller.elementType"
    />

    <!--
      Uploads land in the folder on screen and show up in it, so the index is
      re-requested rather than the page reloaded — there is no page behind a
      modal, and an Inertia visit would replace the one that opened it.

      Wrapped because the button component has two roots — a hidden file input
      alongside the button — and a `slot` attribute on it would carry only to
      the button, leaving the input behind in the index.
    -->
    <div v-if="uploadTarget" slot="secondary-actions">
      <AssetUploadButton
        :can-upload="true"
        :folder-id="uploadTarget.folderId"
        :allowed-kinds="uploadKinds"
        :reload-on-complete="false"
        @uploaded="({id}) => index?.refresh(id)"
      />
    </div>

    <!-- What plugins offer for the folder on screen, as on the assets index. -->
    <div
      v-if="folderActions.length"
      slot="secondary-actions"
      class="flex items-center gap-sm"
    >
      <FolderActionButtons :actions="folderActions" />
    </div>

    <div v-if="createsEntries" slot="secondary-actions">
      <NewEntryButton
        :controller="controller"
        :sources="sources"
        :source="source"
        :site-id="siteId"
        :opener="returnFocus"
        :on-handoff="onHandoff"
      />
    </div>

    <!--
      Assets only: pick a transform instead of plain Select. The controller
      resolves each asset's transformed URL, then submits.
    -->
    <template v-if="transformable">
      <ActionMenu
        slot="primary-actions"
        :actions="transformActions"
        :label="t('Select transform')"
      >
        <template #invoker>
          <craft-button slot="invoker" type="button" variant="fill">
            {{ t('Select transform') }}
          </craft-button>
        </template>
      </ActionMenu>
    </template>
  </craft-element-selector-modal>
</template>
