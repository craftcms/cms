<script setup lang="ts">
  import {computed, ref, useTemplateRef} from 'vue';
  import {t} from '@craftcms/ui';
  import {useImageEditor, type SaveMode} from '../useImageEditor';
  import {
    constraintOptions as buildConstraintOptions,
    defaultConstraintKey,
  } from '../constraints';
  import type {
    EditorView,
    FabricElementHandle,
    RelativeFocalPoint,
  } from '../types';

  const props = defineProps<{
    assetId: number;
    filename: string;
    focalPoint: RelativeFocalPoint | null;
    imageEditorRatios: Record<string, string | number>;
    allowDegreeFractions: boolean;
    orientation: 'ltr' | 'rtl';
  }>();

  /** Two-way, so the host can open it and the dialog can close itself. */
  const opened = defineModel<boolean>('open', {default: false});

  const emit = defineEmits<{
    /** A save landed; `newAssetId` is set when it was saved as a copy. */
    (e: 'saved', result: {newAssetId?: number}): void;
  }>();

  const editorEl = useTemplateRef<HTMLElement>('editorEl');
  const imageCanvasEl = useTemplateRef<HTMLCanvasElement>('imageCanvasEl');
  const croppingCanvasEl =
    useTemplateRef<HTMLCanvasElement>('croppingCanvasEl');

  const editor = useImageEditor({
    assetId: props.assetId,
    focalPoint: props.focalPoint,
    allowDegreeFractions: props.allowDegreeFractions,
    elements: {
      editor: editorEl,
      imageCanvas: imageCanvasEl,
      croppingCanvas: croppingCanvasEl,
    },
  });

  const {state} = editor;

  function onDialogHide(): void {
    opened.value = false;
  }

  const rtl = computed(() => props.orientation === 'rtl');

  const cropOrientation = ref<'landscape' | 'portrait'>('landscape');

  /**
   * The selected constraint, held as its key from `imageEditorRatios` rather
   * than its value.
   *
   * The value inverts with the orientation — `16:9` becomes `9:16` — so keying
   * on it would lose the selection the moment the orientation flipped, and
   * re-apply the ratio the user just switched away from.
   */
  const constraintKey = ref<string>(
    defaultConstraintKey(props.imageEditorRatios)
  );

  const customWidth = ref(1);
  const customHeight = ref(1);
  const straightenValue = ref(0);

  const showingCustomConstraint = computed(
    () => constraintKey.value === 'custom'
  );

  const constraintOptions = computed(() =>
    buildConstraintOptions(props.imageEditorRatios, cropOrientation.value)
  );

  const selectedConstraint = computed(
    () =>
      constraintOptions.value.find(
        (option) => option.key === constraintKey.value
      ) ?? null
  );

  // Handles are named for where they sit on screen, so the pairs swap under
  // RTL. Spelled out rather than interpolated: the catalog keys on the literal
  // source strings, and `t('Left')` inside a template literal would build a
  // key that no longer matches.
  const cropHandles = computed(() =>
    rtl.value
      ? [
          {handle: 'tr', label: t('Top-Right Handle')},
          {handle: 't', label: t('Top Handle')},
          {handle: 'tl', label: t('Top-Left Handle')},
          {handle: 'r', label: t('Right Handle')},
          {handle: 'l', label: t('Left Handle')},
          {handle: 'br', label: t('Bottom-Right Handle')},
          {handle: 'b', label: t('Bottom Handle')},
          {handle: 'bl', label: t('Bottom-Left Handle')},
        ]
      : [
          {handle: 'tl', label: t('Top-Left Handle')},
          {handle: 't', label: t('Top Handle')},
          {handle: 'tr', label: t('Top-Right Handle')},
          {handle: 'l', label: t('Left Handle')},
          {handle: 'r', label: t('Right Handle')},
          {handle: 'bl', label: t('Bottom-Left Handle')},
          {handle: 'b', label: t('Bottom Handle')},
          {handle: 'br', label: t('Bottom-Right Handle')},
        ]
  );

  // Not rendered — carried over from the Twig's `directionalButtons`, which was
  // also only ever `{% set %}`. These are the RTL-aware labels for the arrow-key
  // nudge on a picked-up cropper/handle/focal point, and keeping them here keeps
  // the strings in the translation catalog for the announcements to use.
  const directionalButtons = computed(() =>
    rtl.value
      ? [
          {direction: 'up', label: t('Up')},
          {direction: 'right', label: t('Right')},
          {direction: 'down', label: t('Down')},
          {direction: 'left', label: t('Left')},
        ]
      : [
          {direction: 'up', label: t('Up')},
          {direction: 'left', label: t('Left')},
          {direction: 'down', label: t('Down')},
          {direction: 'right', label: t('Right')},
        ]
  );

  defineExpose({directionalButtons});

  /** `craft-tabs` owns the selection; the editor follows it. */
  function onTabChanged(event: Event): void {
    const index = (event.target as {selectedIndex?: number} | null)
      ?.selectedIndex;
    const views: EditorView[] = ['rotate', 'crop'];

    if (index !== undefined && views[index]) {
      editor.showView(views[index]);
    }
  }

  /** Pushes the selected constraint at the cropper, custom inputs included. */
  function applySelectedConstraint(): void {
    if (constraintKey.value === 'custom') {
      editor.applyCustomConstraint(customWidth.value, customHeight.value);

      return;
    }

    const option = selectedConstraint.value;

    if (option) {
      editor.applyConstraint(option.value);
    }
  }

  function onConstraintChange(key: string): void {
    constraintKey.value = key;
    applySelectedConstraint();
  }

  function onCustomConstraintInput(): void {
    if (customWidth.value > 0 && customHeight.value > 0) {
      editor.applyCustomConstraint(customWidth.value, customHeight.value);
    }
  }

  /** `craft-button-group` reports the newly selected value on itself. */
  function onOrientationChanged(event: Event): void {
    const value = (event.target as {value?: string} | null)?.value;

    if (value === 'landscape' || value === 'portrait') {
      onOrientationChange(value);
    }
  }

  function onOrientationChange(value: 'landscape' | 'portrait'): void {
    if (value === cropOrientation.value) {
      return;
    }

    cropOrientation.value = value;

    // Turn the rectangle rather than rebuilding it from the flipped ratio: the
    // crop the user framed is kept, just stood the other way up. It carries the
    // inverted ratio with it, so the constraint list stays in step.
    editor.turnCrop();
  }

  /** Stable id per option, so each radio's slotted label can target it. */
  function constraintId(key: string): string {
    return `constraint-${key.replace(/[^\w-]+/g, '-')}`;
  }

  function isPressed(handle: FabricElementHandle): boolean {
    if (handle === 'rectangle') {
      return editor.editing.rectanglePickedUp.value;
    }

    if (handle === 'focalpoint') {
      return editor.editing.focalPickedUp.value;
    }

    return editor.editing.pickedHandle.value === handle;
  }

  function onStraightenChange(event: Event): void {
    const value = Number((event.target as HTMLInputElement).value);
    straightenValue.value = value;
    editor.straighten(value);
  }

  /**
   * `save()` resolves to `null` when the request failed or was a no-op, so the
   * return trip only happens on a real save.
   */
  async function onSave(mode: SaveMode): Promise<void> {
    const result = await editor.save(mode);

    if (result) {
      emit('saved', result);
      opened.value = false;
    }
  }

  function onStraightenEnd(): void {
    editor.hideGrid();
    editor.cleanupFocalPointAfterStraighten();
  }
</script>

<template>
  <!-- A native <dialog> via `showModal()`, so focus containment, the Escape
    key and the backdrop are the platform's job rather than ours. -->
  <craft-dialog
    fullscreen
    :open="opened"
    :label="t('Edit Image')"
    @craft-after-show="editor.start"
    @craft-hide="onDialogHide"
  >
    <div class="image-editor">
      <div class="image-editor__tools">
        <h1 class="sr-only">{{ t('Edit Image') }}</h1>

        <!-- `craft-tabs` owns the tablist: it assigns each tab its id, role,
      `aria-controls`/`aria-selected` and roving tabindex, pairs tabs with
      panels by document order, and drives panel visibility. -->
        <craft-tabs @selected-changed="onTabChanged">
          <craft-tab slot="tab">
            <div class="flex items-center gap-1">
              <craft-icon name="rotate" />
              {{ t('Rotate') }}
            </div>
          </craft-tab>
          <div slot="panel" class="rotate">
            <craft-button-group class="rotate-buttons">
              <craft-button
                type="button"
                icon="arrow-rotate-left"
                align="start"
                @click="editor.rotate(-90)"
              >
                {{ t('Rotate Left') }}
              </craft-button>
              <craft-button
                type="button"
                icon="arrow-rotate-right"
                align="start"
                @click="editor.rotate(90)"
              >
                {{ t('Rotate Right') }}
              </craft-button>
            </craft-button-group>

            <craft-button-group class="flip-buttons">
              <craft-button
                type="button"
                icon="arrows-up-down"
                align="start"
                @click="editor.flip('y')"
              >
                {{ t('Flip Vertical') }}
              </craft-button>
              <craft-button
                type="button"
                icon="arrows-left-right"
                align="start"
                @click="editor.flip('x')"
              >
                {{ t('Flip Horizontal') }}
              </craft-button>
            </craft-button-group>

            <craft-button-group>
              <craft-button
                type="button"
                icon="crosshairs"
                align="start"
                :aria-pressed="isPressed('focalpoint') ? 'true' : 'false'"
                data-fabric-element="focalpoint"
                @click="
                  editor.onEditButtonClick(
                    'focalpoint',
                    isPressed('focalpoint'),
                    t('Focal Point')
                  )
                "
                @keydown="editor.onEditButtonKeydown"
                @focus="editor.onEditButtonFocus('focalpoint')"
                @blur="editor.onEditButtonBlur"
              >
                {{ t('Focal Point') }}
              </craft-button>
            </craft-button-group>
          </div>

          <craft-tab slot="tab">
            <div class="flex items-center gap-1">
              <craft-icon name="crop-simple" />
              {{ t('Crop') }}
            </div>
          </craft-tab>
          <div slot="panel" class="crop">
            <craft-field
              v-if="!showingCustomConstraint"
              :label="t('Orientation')"
              fieldset
            >
              <!-- The group owns the selection: with `name` set it toggles
                `active` and `aria-pressed` on its children from its own
                `value`, so the buttons must not set `active` themselves — the
                group strips it again on its next sync. -->
              <craft-button-group
                slot="input"
                id="orientation"
                name="orientation"
                :value="cropOrientation"
                @change="onOrientationChanged"
              >
                <craft-button
                  type="button"
                  value="landscape"
                  icon="image-landscape"
                  :aria-label="t('Landscape')"
                ></craft-button>
                <craft-button
                  type="button"
                  value="portrait"
                  icon="image-portrait"
                  :aria-label="t('Portrait')"
                ></craft-button>
              </craft-button-group>
            </craft-field>

            <craft-field :label="t('Constraints')" fieldset>
              <div slot="input">
                <craft-radio-group name="constraint" class="constraint-group">
                  <craft-radio
                    v-for="option in constraintOptions"
                    :key="option.key"
                    .choiceValue="option.value"
                    .checked="constraintKey === option.key"
                  >
                    <input
                      slot="input"
                      :id="constraintId(option.key)"
                      type="radio"
                      name="constraint"
                      :value="option.value"
                      :checked="constraintKey === option.key"
                      @change="onConstraintChange(option.key)"
                    />
                    <label slot="label" :for="constraintId(option.key)">
                      {{ option.label }}
                    </label>
                  </craft-radio>
                </craft-radio-group>

                <!-- The legacy editor injected these inputs into the constraint
                fieldset from JS; they belong in the template. -->
                <div
                  v-if="showingCustomConstraint"
                  class="constraint custom"
                  role="group"
                  :aria-label="t('Custom')"
                >
                  <input
                    v-model.number="customWidth"
                    type="text"
                    class="custom-constraint-w"
                    size="3"
                    :aria-label="t('Width unit')"
                    @input="onCustomConstraintInput"
                  />
                  <span class="custom-constraint-spacer" aria-hidden="true">
                    x
                  </span>
                  <input
                    v-model.number="customHeight"
                    type="text"
                    class="custom-constraint-h"
                    size="3"
                    :aria-label="t('Height unit')"
                    @input="onCustomConstraintInput"
                  />
                </div>
              </div>
            </craft-field>

            <div role="application">
              <craft-button-group>
                <craft-button
                  type="button"
                  icon="crosshairs"
                  align="start"
                  :aria-pressed="isPressed('focalpoint') ? 'true' : 'false'"
                  data-fabric-element="focalpoint"
                  @click="
                    editor.onEditButtonClick(
                      'focalpoint',
                      isPressed('focalpoint'),
                      t('Focal Point')
                    )
                  "
                  @keydown="editor.onEditButtonKeydown"
                  @focus="editor.onEditButtonFocus('focalpoint')"
                  @blur="editor.onEditButtonBlur"
                >
                  {{ t('Focal Point') }}
                </craft-button>
              </craft-button-group>

              <!-- Keyboard-only, and visually hidden as it was in Craft 5:
                these buttons exist to pick up the rectangle and its handles
                without a pointer. What they're doing is drawn on the canvas —
                the focus ring and the move icon — so showing the controls
                themselves would only duplicate it. -->
              <fieldset data-cropper-edit class="sr-only">
                <legend>
                  {{ t('Edit {type}', {type: t('Cropping Rectangle')}) }}
                </legend>

                <!-- Parsed onto the fabric canvas as the drag affordance, not
              rendered in place. -->
                <div id="move-icon-wrapper" hidden>
                  <craft-icon name="up-down-left-right" />
                </div>

                <div role="application">
                  <div>
                    <craft-button
                      id="cropper-handle"
                      type="button"
                      class="cropper-edit__btn"
                      :aria-pressed="isPressed('rectangle') ? 'true' : 'false'"
                      data-fabric-element="rectangle"
                      @click="
                        editor.onEditButtonClick(
                          'rectangle',
                          isPressed('rectangle'),
                          t('Cropping Rectangle')
                        )
                      "
                      @keydown="editor.onEditButtonKeydown"
                      @focus="editor.onEditButtonFocus('rectangle')"
                      @blur="editor.onEditButtonBlur"
                    >
                      {{ t('Cropping Rectangle') }}
                    </craft-button>
                  </div>

                  <div>
                    <template
                      v-for="button in cropHandles"
                      :key="button.handle"
                    >
                      <craft-button
                        type="button"
                        class="cropper-edit__btn"
                        :aria-pressed="
                          isPressed(button.handle as FabricElementHandle)
                            ? 'true'
                            : 'false'
                        "
                        :data-fabric-element="button.handle"
                        @click="
                          editor.onEditButtonClick(
                            button.handle as FabricElementHandle,
                            isPressed(button.handle as FabricElementHandle),
                            button.label
                          )
                        "
                        @keydown="editor.onEditButtonKeydown"
                        @focus="
                          editor.onEditButtonFocus(
                            button.handle as FabricElementHandle
                          )
                        "
                        @blur="editor.onEditButtonBlur"
                      >
                        {{ button.label }}
                      </craft-button>
                    </template>
                  </div>
                </div>
              </fieldset>
            </div>
          </div>
        </craft-tabs>
      </div>

      <div class="image-container">
        <div
          ref="editorEl"
          class="image"
          :style="{cursor: editor.cursor.value}"
          @pointerdown="editor.onPointerDown"
          @pointermove="editor.onPointerMove"
          @pointerup="editor.onPointerUp"
          @pointerleave="editor.onPointerLeave"
        >
          <canvas ref="imageCanvasEl" id="image-canvas"></canvas>
          <canvas ref="croppingCanvasEl" id="cropping-canvas"></canvas>
          <craft-spinner v-if="!editor.isReady.value" class="editor-spinner" />
        </div>

        <div v-show="state.currentView.value === 'rotate'" class="image-tools">
          <div class="straightening">
            <craft-slide-rule
              id="slide-rule"
              :label="t('Rotate')"
              :value="straightenValue"
              @start="editor.showGrid"
              @change="onStraightenChange"
              @end="onStraightenEnd"
            />
          </div>
        </div>
      </div>
    </div>

    <div slot="footer" class="image-editor__actions">
      <craft-button
        type="button"
        :loading="editor.savingAs.value === 'copy'"
        :disabled="editor.isSaving.value"
        @click="onSave('copy')"
      >
        {{ t('Save as a new asset') }}
      </craft-button>
      <craft-button
        type="button"
        variant="primary"
        :loading="editor.savingAs.value === 'replace'"
        :disabled="editor.isSaving.value"
        @click="onSave('replace')"
      >
        {{ t('Save') }}
      </craft-button>
    </div>
  </craft-dialog>
</template>

<style scoped lang="scss">
  // The legacy rules are all scoped under `.modal.imageeditor`, so none of them
  // reach this page. Only the structural bits the canvas needs are here; the
  // rest of the styling comes with the real port.
  //
  // The chain down to `.image` is load-bearing, not cosmetic: the editor
  // measures that element to decide how big to draw, so every ancestor needs a
  // definite height or it measures zero and draws nothing.
  .image-editor {
    display: grid;
    // The fullscreen dialog's surface is a header/body/footer grid whose body
    // row takes the remaining height, so `100%` resolves against a real number
    // here — no viewport arithmetic needed.
    block-size: 100%;
    min-block-size: 0;
    grid-template-columns: 260px 1fr;
    gap: var(--c-spacing-lg);
  }

  .image-editor__actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--c-spacing-sm);
  }

  .image-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
    min-inline-size: 0;
    background-color: var(--c-color-black-fill-loud);
  }

  .image {
    flex: 1;
    position: relative;
    // Without this the browser claims touch drags for panning and the pointer
    // stream is cancelled mid-gesture.
    touch-action: none;
    // Without this a flex item refuses to shrink below its content.
    min-block-size: 0;

    canvas {
      position: absolute;
      inset-block-start: 0;
      inset-inline-start: 0;
    }
  }

  .image-tools {
    flex: none;
    text-align: center;
  }

  .editor-spinner {
    position: absolute;
    inset-block-start: 50%;
    inset-inline-start: 50%;
    translate: -50% -50%;
  }
</style>
