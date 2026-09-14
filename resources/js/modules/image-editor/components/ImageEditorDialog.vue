<script setup lang="ts">
import { computed, ref, useTemplateRef, watch } from "vue";
import { useEventListener } from "@vueuse/core";
import { t } from "@craftcms/ui";
import { useImageEditor, type SaveMode, type SaveResult } from "../useImageEditor";
import { constraintOptions as buildConstraintOptions, defaultConstraintKey } from "../constraints";
import type { EditorView, FabricElementHandle, RelativeFocalPoint } from "../types";

const props = defineProps<{
  assetId: number;
  filename: string;
  focalPoint: RelativeFocalPoint | null;
  imageWidth: number | null;
  imageHeight: number | null;
  imageEditorRatios: Record<string, string | number>;
  allowDegreeFractions: boolean;
  orientation: "ltr" | "rtl";
}>();

/** Two-way, so the host can open it and the dialog can close itself. */
const opened = defineModel<boolean>("open", { default: false });

const emit = defineEmits<{
  /** A save landed; the `newAsset*` fields are set when it was saved as a copy. */
  (e: "saved", result: SaveResult): void;
}>();

const editorEl = useTemplateRef<HTMLElement>("editorEl");
const imageCanvasEl = useTemplateRef<HTMLCanvasElement>("imageCanvasEl");
const croppingCanvasEl = useTemplateRef<HTMLCanvasElement>("croppingCanvasEl");

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

const { state } = editor;

function onDialogHide(): void {
  opened.value = false;
}

const rtl = computed(() => props.orientation === "rtl");

/**
 * Which way round the picture already is — where the crop starts, and what
 * resetting returns to. Square images and ones with no dimensions on record
 * read as landscape, matching the button that's selected for them.
 */
const naturalOrientation = computed<"landscape" | "portrait">(() =>
  props.imageWidth !== null && props.imageHeight !== null && props.imageHeight > props.imageWidth
    ? "portrait"
    : "landscape",
);

const cropOrientation = ref<"landscape" | "portrait">(naturalOrientation.value);

/**
 * The selected constraint, held as its key from `imageEditorRatios` rather
 * than its value.
 *
 * The value inverts with the orientation — `16:9` becomes `9:16` — so keying
 * on it would lose the selection the moment the orientation flipped, and
 * re-apply the ratio the user just switched away from.
 */
const constraintKey = ref<string>(defaultConstraintKey(props.imageEditorRatios));

const customWidth = ref(1);
const customHeight = ref(1);
const straightenValue = ref(0);

const showingCustomConstraint = computed(() => constraintKey.value === "custom");

const constraintOptions = computed(() =>
  buildConstraintOptions(props.imageEditorRatios, cropOrientation.value),
);

const selectedConstraint = computed(
  () => constraintOptions.value.find((option) => option.key === constraintKey.value) ?? null,
);

const cropHandles = computed(() =>
  rtl.value
    ? [
        { handle: "tr", label: t("Top-Right Handle") },
        { handle: "t", label: t("Top Handle") },
        { handle: "tl", label: t("Top-Left Handle") },
        { handle: "r", label: t("Right Handle") },
        { handle: "l", label: t("Left Handle") },
        { handle: "br", label: t("Bottom-Right Handle") },
        { handle: "b", label: t("Bottom Handle") },
        { handle: "bl", label: t("Bottom-Left Handle") },
      ]
    : [
        { handle: "tl", label: t("Top-Left Handle") },
        { handle: "t", label: t("Top Handle") },
        { handle: "tr", label: t("Top-Right Handle") },
        { handle: "l", label: t("Left Handle") },
        { handle: "r", label: t("Right Handle") },
        { handle: "bl", label: t("Bottom-Left Handle") },
        { handle: "b", label: t("Bottom Handle") },
        { handle: "br", label: t("Bottom-Right Handle") },
      ],
);

const directionalButtons = computed(() =>
  rtl.value
    ? [
        { direction: "up", label: t("Up") },
        { direction: "right", label: t("Right") },
        { direction: "down", label: t("Down") },
        { direction: "left", label: t("Left") },
      ]
    : [
        { direction: "up", label: t("Up") },
        { direction: "left", label: t("Left") },
        { direction: "down", label: t("Down") },
        { direction: "right", label: t("Right") },
      ],
);

defineExpose({ directionalButtons });

/**
 * The controls the editor can't see: a history step puts these back alongside
 * the image, so a restored crop doesn't sit under the wrong constraint.
 */
editor.setUiAdapter({
  capture: () => ({
    constraintKey: constraintKey.value,
    cropOrientation: cropOrientation.value,
    customWidth: customWidth.value,
    customHeight: customHeight.value,
  }),
  apply: (ui) => {
    const restored = ui as {
      constraintKey: string;
      cropOrientation: "landscape" | "portrait";
      customWidth: number;
      customHeight: number;
    } | null;

    if (restored) {
      constraintKey.value = restored.constraintKey;
      cropOrientation.value = restored.cropOrientation;
      customWidth.value = restored.customWidth;
      customHeight.value = restored.customHeight;
    }

    // The rule holds its own copy of the angle; follow the editor back.
    straightenValue.value = editor.state.imageStraightenAngle.value;
  },
});

const tabsEl = useTemplateRef<HTMLElement & { selectedIndex: number }>("tabsEl");

// An undo can move the editor to the other tab's view. Setting the strip's
// selection fires `selected-changed`, which asks for the view it's already on.
watch(
  () => editor.state.currentView.value,
  (view) => {
    const index = view === "crop" ? 1 : 0;

    if (tabsEl.value && tabsEl.value.selectedIndex !== index) {
      tabsEl.value.selectedIndex = index;
    }
  },
);

const isMac = /Mac|iPhone|iPad/.test(navigator.platform);
const undoLabel = computed(() => `${t("Undo")} (${isMac ? "⌘Z" : "Ctrl+Z"})`);
const redoLabel = computed(() => `${t("Redo")} (${isMac ? "⇧⌘Z" : "Ctrl+Y"})`);

/** A field keeps its own undo for the text being typed into it. */
function isTypingIn(event: KeyboardEvent): boolean {
  const target = event.composedPath()[0];

  return (
    target instanceof HTMLElement &&
    (target.isContentEditable ||
      ["INPUT", "TEXTAREA", "SELECT"].includes(target.tagName))
  );
}

function onHistoryShortcut(event: KeyboardEvent): void {
  if (
    !opened.value ||
    event.defaultPrevented ||
    event.altKey ||
    !(event.metaKey || event.ctrlKey) ||
    isTypingIn(event)
  ) {
    return;
  }

  const key = event.key.toLowerCase();

  if (key === "z" && !event.shiftKey) {
    event.preventDefault();
    editor.undo();
  } else if (
    (key === "z" && event.shiftKey) ||
    (key === "y" && event.ctrlKey && !event.metaKey)
  ) {
    event.preventDefault();
    editor.redo();
  }
}

useEventListener(document, "keydown", onHistoryShortcut);

/** Discards every edit, after asking. The dialog stays open. */
function onReset(): void {
  if (!window.confirm(t("Discard all changes to this image?"))) {
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
    !window.confirm(t("Any changes will be lost if you close the image editor."))
  ) {
    event.preventDefault();
  }
}

/** `craft-tabs` owns the selection; the editor follows it. */
function onTabChanged(event: Event): void {
  const index = (event.target as { selectedIndex?: number } | null)?.selectedIndex;
  const views: EditorView[] = ["rotate", "crop"];

  if (index !== undefined && views[index]) {
    editor.showView(views[index]);
  }
}

/** Pushes the selected constraint at the cropper, custom inputs included. */
function applySelectedConstraint(): void {
  if (constraintKey.value === "custom") {
    editor.applyCustomConstraint(customWidth.value, customHeight.value);

    return;
  }

  const option = selectedConstraint.value;

  if (option) {
    editor.applyConstraint(option.value);
  }
}

// Recorded around the control change as well as the reshape it causes, so
// undoing it puts the previous selection back too.
function onConstraintChange(key: string): void {
  editor.recordChange(() => {
    constraintKey.value = key;
    applySelectedConstraint();
  });
}

function onCustomConstraintInput(): void {
  if (customWidth.value > 0 && customHeight.value > 0) {
    editor.recordChange(
      () => editor.applyCustomConstraint(customWidth.value, customHeight.value),
      "custom-constraint",
    );
  }
}

/** `craft-button-group` reports the newly selected value on itself. */
function onOrientationChanged(event: Event): void {
  const value = (event.target as { value?: string } | null)?.value;

  if (value === "landscape" || value === "portrait") {
    onOrientationChange(value);
  }
}

function onOrientationChange(value: "landscape" | "portrait"): void {
  if (value === cropOrientation.value) {
    return;
  }

  // Turn the rectangle rather than rebuilding it from the flipped ratio: the
  // crop the user framed is kept, just stood the other way up. It carries the
  // inverted ratio with it, so the constraint list stays in step.
  editor.recordChange(() => {
    cropOrientation.value = value;
    editor.turnCrop();
  });
}

/** Stable id per option, so each radio's slotted label can target it. */
function constraintId(key: string): string {
  return `constraint-${key.replace(/[^\w-]+/g, "-")}`;
}

/**
 * Whether the asset has a focal point at all.
 *
 * Distinct from `focalPickedUp`, which is the transient "grabbed for moving"
 * state and flips when the marker is clicked on the canvas. The button is a
 * toggle for the focal point itself, so it tracks existence.
 */
const focalPointActive = computed(() => editor.state.focalPoint.value !== null);

function isPressed(handle: FabricElementHandle): boolean {
  if (handle === "rectangle") {
    return editor.editing.rectanglePickedUp.value;
  }

  if (handle === "focalpoint") {
    return focalPointActive.value;
  }

  return editor.editing.pickedHandle.value === handle;
}

/** Set while the rule is being dragged, which records as one gesture. */
let straightening = false;

function onStraightenStart(): void {
  straightening = true;
  editor.showGrid();
  editor.beginChange();
}

function onStraightenChange(event: Event): void {
  const value = Number((event.target as HTMLInputElement).value);
  const apply = () => {
    straightenValue.value = value;
    editor.straighten(value);
  };

  // The arrow keys change the rule without a start or end, so a run of them
  // is merged into one step instead.
  if (straightening) {
    apply();
  } else {
    editor.recordChange(apply, "straighten-keyboard");
  }
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
    emit("saved", result);
  }
}

function onStraightenEnd(): void {
  editor.hideGrid();
  editor.cleanupFocalPointAfterStraighten();
  straightening = false;
  editor.commitChange();
}
</script>

<template>
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
      <div class="image-editor">
        <div class="image-editor__sidebar">
          <craft-tabs ref="tabsEl" @selected-changed="onTabChanged">
            <craft-tab slot="tab">
              <div class="flex items-center gap-1">
                <craft-icon name="rotate" />
                {{ t("Rotate") }}
              </div>
            </craft-tab>
            <div slot="panel">
              <div class="image-editor__panel">
                <div id="rotate-buttons" class="flex flex-wrap gap-1">
                  <craft-button
                    type="button"
                    icon="arrow-rotate-left"
                    align="start"
                    @click="editor.rotate(-90)"
                  >
                    {{ t("Rotate Left") }}
                  </craft-button>
                  <craft-button
                    type="button"
                    icon="arrow-rotate-right"
                    align="start"
                    @click="editor.rotate(90)"
                  >
                    {{ t("Rotate Right") }}
                  </craft-button>
                </div>

                <div id="flip-buttons" class="flex flex-wrap gap-1">
                  <craft-button
                    type="button"
                    icon="arrows-up-down"
                    align="start"
                    @click="editor.flip('y')"
                  >
                    {{ t("Flip Vertical") }}
                  </craft-button>
                  <craft-button
                    type="button"
                    icon="arrows-left-right"
                    align="start"
                    @click="editor.flip('x')"
                  >
                    {{ t("Flip Horizontal") }}
                  </craft-button>
                </div>

                <div class="flex gap-1">
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
                        t('Focal Point'),
                      )
                    "
                    @keydown="editor.onEditButtonKeydown"
                    @focus="editor.onEditButtonFocus('focalpoint')"
                    @blur="editor.onEditButtonBlur"
                  >
                    {{ t("Focal Point") }}
                  </craft-button>
                </div>
              </div>
            </div>

            <craft-tab slot="tab">
              <div class="flex items-center gap-1">
                <craft-icon name="crop-simple" />
                {{ t("Crop") }}
              </div>
            </craft-tab>
            <div slot="panel">
              <div class="image-editor__panel crop-tools">
                <craft-field-group>
                  <craft-field v-if="!showingCustomConstraint" :label="t('Orientation')" fieldset>
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
                        <span class="custom-constraint-spacer" aria-hidden="true"> x </span>
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
                  <!-- Keyboard-only controls for the rectangle and its handles;
              their state is drawn on the canvas. -->
                  <fieldset data-cropper-edit class="sr-only">
                    <legend>
                      {{ t("Edit {type}", { type: t("Cropping Rectangle") }) }}
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
                              t('Cropping Rectangle'),
                            )
                          "
                          @keydown="editor.onEditButtonKeydown"
                          @focus="editor.onEditButtonFocus('rectangle')"
                          @blur="editor.onEditButtonBlur"
                        >
                          {{ t("Cropping Rectangle") }}
                        </craft-button>
                      </div>

                      <div>
                        <template v-for="button in cropHandles" :key="button.handle">
                          <craft-button
                            type="button"
                            class="cropper-edit__btn"
                            :aria-pressed="
                              isPressed(button.handle as FabricElementHandle) ? 'true' : 'false'
                            "
                            :data-fabric-element="button.handle"
                            @click="
                              editor.onEditButtonClick(
                                button.handle as FabricElementHandle,
                                isPressed(button.handle as FabricElementHandle),
                                button.label,
                              )
                            "
                            @keydown="editor.onEditButtonKeydown"
                            @focus="editor.onEditButtonFocus(button.handle as FabricElementHandle)"
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
          </craft-tabs>
        </div>
        <div class="image-editor__image">
          <div class="image-container">
            <div
              ref="editorEl"
              class="image"
              :style="{ cursor: editor.cursor.value }"
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
                  @start="onStraightenStart"
                  @change="onStraightenChange"
                  @end="onStraightenEnd"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div slot="footer" class="w-full flex gap-6 items-center justify-between">
      <div class="flex gap-2 items-center">
        <craft-button
          type="button"
          icon="arrow-turn-left"
          :aria-label="undoLabel"
          :title="undoLabel"
          :disabled="!editor.canUndo.value || undefined"
          @click="editor.undo"
        ></craft-button>
        <craft-button
          type="button"
          icon="arrow-turn-right"
          :aria-label="redoLabel"
          :title="redoLabel"
          :disabled="!editor.canRedo.value || undefined"
          @click="editor.redo"
        ></craft-button>
        <craft-button
          type="button"
          icon="arrow-rotate-left"
          :disabled="!editor.isDirty.value || undefined"
          @click="onReset"
        >
          {{ t("Reset to original") }}
        </craft-button>
      </div>
      <div class="flex gap-2 items-center">
        <craft-button
          type="button"
          :loading="editor.savingAs.value === 'copy'"
          :disabled="editor.isSaving.value"
          @click="onSave('copy')"
        >
          {{ t("Save as a new asset") }}
        </craft-button>
        <craft-button
          type="button"
          variant="primary"
          :loading="editor.savingAs.value === 'replace'"
          :disabled="editor.isSaving.value"
          @click="onSave('replace')"
        >
          {{ t("Save") }}
        </craft-button>
      </div>
    </div>
  </craft-dialog>
</template>

<style scoped lang="scss">
// Every ancestor of `.image` needs a definite height: the editor measures it
// to size the canvas. The body is edge to edge; header and footer keep their
// padding.
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
  display: flex;
  flex-direction: column;
  max-width: 100dvw;

  @media screen and (min-width: 768px) {
    flex-direction: row;
  }
}

.image-editor__sidebar {
  flex: 0 0 clamp(calc(260rem / 16), 25%, calc(320rem / 16));

  @media screen and (min-width: 768px) {
    border-inline-end: 1px solid var(--c-color-neutral-border-quiet);
  }
}

.image-editor__panel {
  display: flex;
  flex-direction: column;
  gap: var(--c-spacing-lg);
  padding: var(--c-spacing-lg);
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

.straightening {
  max-width: 400px;
  margin: 0 auto;
}
</style>
