<script setup lang="ts">
  import {computed, ref, useTemplateRef} from 'vue';
  import {t} from '@craftcms/ui';
  import {
    useImageEditor,
    type SaveMode,
    type SaveResult,
  } from '../useImageEditor';
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
    imageWidth: number | null;
    imageHeight: number | null;
    imageEditorRatios: Record<string, string | number>;
    allowDegreeFractions: boolean;
    orientation: 'ltr' | 'rtl';
  }>();

  /** Two-way, so the host can open it and the dialog can close itself. */
  const opened = defineModel<boolean>('open', {default: false});

  const emit = defineEmits<{
    /** A save landed; the `newAsset*` fields are set when it was saved as a copy. */
    (e: 'saved', result: SaveResult): void;
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

  /**
   * Which way round the picture already is — where the crop starts, and what
   * resetting returns to. Square images and ones with no dimensions on record
   * read as landscape, matching the button that's selected for them.
   */
  const naturalOrientation = computed<'landscape' | 'portrait'>(() =>
    props.imageWidth !== null &&
    props.imageHeight !== null &&
    props.imageHeight > props.imageWidth
      ? 'portrait'
      : 'landscape'
  );

  const cropOrientation = ref<'landscape' | 'portrait'>(
    naturalOrientation.value
  );

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

  /**
   * Whether the editor is in crop mode.
   *
   * Read off the editor rather than tracked here: `showView` owns the canvas side
   * of the switch — building and tearing down the cropping layer — so a second
   * copy of this would be one more thing to keep in step.
   */
  const cropping = computed(() => editor.state.currentView.value === 'crop');

  /** The Crop button is a toggle, so it moves back to the rotate view too. */
  function onCropToggle(): void {
    editor.showView(cropping.value ? 'rotate' : 'crop');
  }

  /** Discards every edit, after asking. The dialog stays open. */
  function onReset(): void {
    if (!window.confirm(t('Discard all changes to this image?'))) {
      return;
    }

    editor.reset();

    // The controls hold their own copy of what they last applied, so they follow
    // the editor back rather than showing settings that no longer apply.
    straightenValue.value = 0;
    cropOrientation.value = naturalOrientation.value;
    constraintKey.value = defaultConstraintKey(props.imageEditorRatios);
    customWidth.value = 1;
    customHeight.value = 1;
  }

  /**
   * Guards the way out. `craft-before-hide` covers every dismissal — the close
   * button, Escape and the backdrop — so this asks once for all of them.
   */
  function onBeforeHide(event: Event): void {
    if (
      editor.isDirty.value &&
      !window.confirm(
        t('Any changes will be lost if you close the image editor.')
      )
    ) {
      event.preventDefault();
    }
  }

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

  /**
   * Whether the asset has a focal point at all.
   *
   * Distinct from `focalPickedUp`, which is the transient "grabbed for moving"
   * state and flips when the marker is clicked on the canvas. The button is a
   * toggle for the focal point itself, so it tracks existence.
   */
  const focalPointActive = computed(
    () => editor.state.focalPoint.value !== null
  );

  function isPressed(handle: FabricElementHandle): boolean {
    if (handle === 'rectangle') {
      return editor.editing.rectanglePickedUp.value;
    }

    if (handle === 'focalpoint') {
      return focalPointActive.value;
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
      // Close before handing the result up, so a consumer that navigates on
      // the way out isn't doing it while we still read as open.
      opened.value = false;
      emit('saved', result);
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
  <!-- The editor is always dark, whatever the CP is set to: judging an image
    against a light chrome skews how it reads. `[data-theme='dark']` is a plain
    attribute selector, so it redefines the colour tokens from here down —
    custom properties inherit through the slot and into each component's shadow
    root, so everything inside follows. -->
  <!-- `.opened` binds the property, not the `open` attribute. Vue only strips a
    false boolean for the seven names in its `isSpecialBooleanAttr` list, and
    `open` isn't one of them, so `:open="false"` writes `open="false"` — which
    Lit reads as present, and the dialog opens itself on load. -->
  <craft-dialog
    data-theme="dark"
    fullscreen
    .opened="opened"
    :label="t('Edit Image')"
    @craft-after-show="editor.start"
    @craft-before-hide="onBeforeHide"
    @craft-hide="onDialogHide"
  >
    <div class="flex flex-col h-full">
      <div
        id="image-editor-toolbar"
        class="image-editor__toolbar flex gap-6 p-2"
      >
        <div id="rotate-buttons" class="flex gap-1">
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
        </div>

        <div id="flip-buttons" class="flex gap-1">
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
        </div>

        <div id="focal-point" class="flex gap-1">
          <craft-button
            type="button"
            icon="crosshairs"
            align="start"
            toggle
            :active="focalPointActive"
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
        </div>
        <div id="crop" class="flex gap-1">
          <craft-button
            type="button"
            icon="crop-simple"
            toggle
            :active="cropping"
            @click="onCropToggle"
          >
            {{ t('Crop') }}
          </craft-button>
        </div>
        <!-- `ms-auto` pushes it to the far end of the toolbar, away from the
        tools, since it undoes rather than does. -->
        <div id="reset" class="flex gap-1 ms-auto">
          <craft-button
            type="button"
            icon="arrow-rotate-left"
            :disabled="!editor.isDirty.value || undefined"
            @click="onReset"
          >
            {{ t('Reset to original') }}
          </craft-button>
        </div>
      </div>
      <div
        :class="{
          'image-editor': true,
          'image-editor--has-sidebar': cropping,
        }"
      >
        <div class="image-editor__sidebar">
          <div class="crop-tools">
            <craft-field-group>
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
            </craft-field-group>

            <div role="application">
              <craft-button-group> </craft-button-group>

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
        </div>
        <div class="image-editor__image">
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
              <craft-spinner
                v-if="!editor.isReady.value"
                class="editor-spinner"
              />
            </div>

            <div
              v-show="state.currentView.value === 'rotate'"
              class="image-tools"
            >
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
      </div>
    </div>

    <div slot="footer" class="flex gap-2">
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
  // The editor fills the body edge to edge — the image sits on its own dark
  // surround, and padding would frame it in the dialog's colour instead. Header
  // and footer keep theirs, so only this part is reached for.
  craft-dialog::part(body) {
    padding: 0;
  }

  .image-editor {
    flex: 1;
    // The fullscreen dialog's surface is a header/body/footer grid whose body
    // row takes the remaining height, so `100%` resolves against a real number
    // here — no viewport arithmetic needed.
    block-size: 100%;
    min-block-size: 0;
    gap: var(--c-spacing-lg);
    display: flex;
  }

  // Both tracks need stating. The sidebar holds `craft-field-group` and
  // `craft-field`, which are block-level and so fill their parent rather than
  // asking for a width of their own — left to `flex-basis: auto` it has nothing
  // to size itself from and collapses.
  .image-editor__sidebar {
    padding: var(--c-spacing-lg);
    flex: 0 0 clamp(260px, 25%, 320px);
  }

  // `display: none` rather than hiding it visually: the sidebar holds the
  // cropper's keyboard controls, and leaving those tab-reachable while there is
  // no cropper to drive would be worse than not showing them.
  .image-editor:not(.image-editor--has-sidebar) .image-editor__sidebar {
    display: none;
  }

  .image-editor__image {
    display: flex;
    flex: 1;
    // A flex item won't shrink past its min-content without this, and the
    // canvas side would push the sidebar out instead of giving way.
    min-inline-size: 0;
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
