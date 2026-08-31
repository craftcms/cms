<script setup lang="ts">
  import {computed, onMounted, onUnmounted, ref} from 'vue';
  import {t, type ElementSelectorController} from '@craftcms/ui';
  import '@craftcms/ui/components/element-selector-modal/element-selector-modal';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import ModalElementIndex from './ModalElementIndex.vue';
  import type {SelectedElement} from './useModalElementIndex';
  import {useElementSelectorController} from './useElementSelectorController';

  const props = defineProps<{controller: ElementSelectorController}>();

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

  const index = ref<InstanceType<typeof ModalElementIndex> | null>(null);

  onMounted(() => {
    props.controller.attachIndex({
      clearSelection: () => index.value?.clearSelection(),
    });
  });

  onUnmounted(() => props.controller.detachIndex());
</script>

<template>
  <craft-element-selector-modal :controller="controller">
    <!--
      The index talks to the controller, never to the chrome around it — it emits
      what happened and the controller decides what that means. That is what lets
      the same index sit inside the web component here and inside a plugin's own
      presentation elsewhere.
    -->
    <ModalElementIndex
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
    />

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
          <craft-button type="button" variant="fill">
            {{ t('Select transform') }}
          </craft-button>
        </template>
      </ActionMenu>
    </template>
  </craft-element-selector-modal>
</template>
