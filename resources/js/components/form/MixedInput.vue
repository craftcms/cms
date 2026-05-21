<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {nextTick, onMounted, shallowRef, useTemplateRef, watch} from 'vue';

  export type MixedInputPart = string | [string, string];

  export interface MixedInputToken {
    name: string;
    value: string;
  }

  const textPartPadding = 20;

  const model = defineModel<Array<MixedInputPart>>({required: true});

  withDefaults(
    defineProps<{
      disabled?: boolean;
      invalid?: boolean;
      ariaLabel?: string;
    }>(),
    {
      disabled: false,
      invalid: false,
      ariaLabel: () => t('Input'),
    }
  );

  const root = useTemplateRef<HTMLElement>('root');
  const textMeasure = useTemplateRef<HTMLElement>('textMeasure');
  const focusedTextPart = shallowRef<{
    index: number;
    selectionStart: number;
    selectionEnd: number;
  } | null>(null);
  const selectedTokenIndex = shallowRef<number | null>(null);
  const textPartWidths = shallowRef<Record<number, string>>({});

  function isToken(part: MixedInputPart): part is [string, string] {
    return Array.isArray(part);
  }

  function textPartWidth(index: number): string {
    return textPartWidths.value[index] ?? `${textPartPadding}px`;
  }

  function measureTextPartWidth(value: string): number {
    const stage = textMeasure.value;

    if (!stage) {
      return textPartPadding;
    }

    stage.textContent = value;

    return Math.ceil(stage.getBoundingClientRect().width + textPartPadding);
  }

  function updateTextPartWidths() {
    const widths: Record<number, string> = {};

    model.value.forEach((part, index) => {
      if (typeof part === 'string') {
        widths[index] = `${measureTextPartWidth(part)}px`;
      }
    });

    textPartWidths.value = widths;
  }

  function elementAt(index: number): HTMLElement | null {
    return (
      root.value?.querySelector<HTMLElement>(
        `[data-mixed-input-index="${index}"]`
      ) ?? null
    );
  }

  function isModifierKeyPressed(event: KeyboardEvent): boolean {
    return event.ctrlKey || event.metaKey;
  }

  function inputFromEvent(event: Event): HTMLInputElement {
    return event.currentTarget as HTMLInputElement;
  }

  function rememberCursor(event: Event, index: number) {
    const input = inputFromEvent(event);
    selectedTokenIndex.value = null;
    focusedTextPart.value = {
      index,
      selectionStart: input.selectionStart ?? input.value.length,
      selectionEnd: input.selectionEnd ?? input.value.length,
    };
  }

  function normalizeEditableParts(parts: Array<MixedInputPart>): {
    parts: Array<MixedInputPart>;
    changed: boolean;
  } {
    const normalized: Array<MixedInputPart> = [];
    let changed = false;

    parts.forEach((part) => {
      const previous = normalized[normalized.length - 1];

      if (typeof part === 'string') {
        if (typeof previous === 'string') {
          normalized[normalized.length - 1] = previous + part;
          changed = true;
        } else {
          normalized.push(part);
        }

        return;
      }

      if (typeof previous !== 'string') {
        normalized.push('');
        changed = true;
      }

      normalized.push(part);
    });

    if (
      normalized.length === 0 ||
      typeof normalized[normalized.length - 1] !== 'string'
    ) {
      normalized.push('');
      changed = true;
    }

    return {
      parts: normalized,
      changed: changed || normalized.length !== parts.length,
    };
  }

  function ensureEditableStructure() {
    const normalized = normalizeEditableParts(model.value);

    if (normalized.changed) {
      model.value = normalized.parts;
    }
  }

  function caretPosition(
    element: HTMLInputElement,
    caret?: 'start' | 'end' | number
  ): number {
    if (caret === 'start') {
      return 0;
    }

    if (caret === 'end' || typeof caret === 'undefined') {
      return element.value.length;
    }

    return caret;
  }

  function focusElement(index: number, caret?: 'start' | 'end' | number) {
    nextTick(() => {
      const element = elementAt(index);

      if (!element) {
        return;
      }

      element.focus();

      if (element instanceof HTMLInputElement) {
        const position = caretPosition(element, caret);

        element.setSelectionRange(position, position);
        focusedTextPart.value = {
          index,
          selectionStart: position,
          selectionEnd: position,
        };
        selectedTokenIndex.value = null;
      } else {
        focusedTextPart.value = null;
        selectedTokenIndex.value = index;
      }
    });
  }

  function focusPreviousElement(index: number) {
    if (index <= 0) {
      return;
    }

    focusElement(index - 1, 'end');
  }

  function focusNextElement(index: number) {
    if (index >= model.value.length - 1) {
      return;
    }

    focusElement(index + 1, 'start');
  }

  function focusStart() {
    ensureEditableStructure();
    focusElement(0, 'start');
  }

  function focusEnd() {
    ensureEditableStructure();
    focusElement(model.value.length - 1, 'end');
  }

  function updateTextPart(index: number, value: string) {
    const parts = [...model.value];
    parts[index] = value;
    model.value = parts;
  }

  function handleTextInput(event: Event, index: number) {
    updateTextPart(index, inputFromEvent(event).value);
  }

  function addToken(token: MixedInputToken) {
    const focused = focusedTextPart.value;
    const selected = selectedTokenIndex.value;
    selectedTokenIndex.value = null;

    if (focused && typeof model.value[focused.index] === 'string') {
      const parts = [...model.value];
      const text = parts[focused.index] as string;
      const before = text.slice(0, focused.selectionStart);
      const after = text.slice(focused.selectionEnd);

      parts.splice(focused.index, 1, before, [token.name, token.value], after);
      model.value = parts;
      focusedTextPart.value = {
        index: focused.index + 2,
        selectionStart: 0,
        selectionEnd: 0,
      };
      focusElement(focused.index + 1);

      return;
    }

    if (selected !== null) {
      const parts = [...model.value];
      parts.splice(selected + 1, 0, '', [token.name, token.value]);
      model.value = parts;
      focusElement(selected + 2);

      return;
    }

    const parts = [...model.value];
    parts.push([token.name, token.value], '');
    focusedTextPart.value = {
      index: parts.length - 1,
      selectionStart: 0,
      selectionEnd: 0,
    };
    model.value = parts;
    focusElement(parts.length - 2);
  }

  function removeToken(index: number) {
    const parts = [...model.value];
    parts.splice(index, 1);
    selectedTokenIndex.value = null;

    const previousPart = parts[index - 1];
    const nextPart = parts[index];

    if (typeof previousPart === 'string' && typeof nextPart === 'string') {
      const caret = previousPart.length;
      parts.splice(index - 1, 2, previousPart + nextPart);
      model.value = parts;
      focusElement(index - 1, caret);

      return;
    }

    model.value = parts.length ? parts : [''];
    focusElement(Math.max(0, Math.min(index - 1, model.value.length - 1)));
  }

  function selectToken(index: number) {
    selectedTokenIndex.value = index;
    focusedTextPart.value = null;
  }

  function handleTokenKeydown(event: KeyboardEvent, index: number) {
    switch (event.key) {
      case 'ArrowLeft':
        event.preventDefault();
        if (isModifierKeyPressed(event)) {
          focusStart();
        } else {
          focusPreviousElement(index);
        }
        return;
      case 'ArrowRight':
        event.preventDefault();
        if (isModifierKeyPressed(event)) {
          focusEnd();
        } else {
          focusNextElement(index);
        }
        return;
      case 'Backspace':
      case 'Delete':
        event.preventDefault();
        removeToken(index);
        return;
    }
  }

  function handleTextKeydown(event: KeyboardEvent, index: number) {
    const input = inputFromEvent(event);
    const selectionStart = input.selectionStart ?? 0;
    const selectionEnd = input.selectionEnd ?? input.value.length;

    switch (event.key) {
      case 'ArrowLeft':
        if (isModifierKeyPressed(event)) {
          event.preventDefault();
          focusStart();
          return;
        }

        if (selectionStart === 0 && selectionEnd === 0) {
          event.preventDefault();
          focusPreviousElement(index);
        }
        return;
      case 'ArrowRight':
        if (isModifierKeyPressed(event)) {
          event.preventDefault();
          focusEnd();
          return;
        }

        if (
          selectionStart === input.value.length &&
          selectionEnd === input.value.length
        ) {
          event.preventDefault();
          focusNextElement(index);
        }
        return;
      case 'Backspace':
      case 'Delete':
        if (selectionStart === 0 && selectionEnd === 0) {
          event.preventDefault();
          focusPreviousElement(index);
        }
        return;
    }
  }

  watch(
    model,
    () => {
      ensureEditableStructure();
      nextTick(updateTextPartWidths);
    },
    {immediate: true}
  );

  onMounted(updateTextPartWidths);

  defineExpose({addToken});
</script>

<template>
  <div ref="root" class="mixed-input" tabindex="0" @focus.self="focusEnd">
    <div
      :class="{
        'mixed-input__editor': true,
        'mixed-input__editor--error': invalid,
      }"
    >
      <template v-for="(part, index) in model" :key="index">
        <button
          v-if="isToken(part)"
          :class="{
            'mixed-input__token': true,
            'mixed-input__token--editable': true,
            'mixed-input__token--selected': selectedTokenIndex === index,
          }"
          type="button"
          :data-mixed-input-index="index"
          :aria-pressed="selectedTokenIndex === index"
          :disabled="disabled"
          @click="selectToken(index)"
          @focus="selectToken(index)"
          @keydown="handleTokenKeydown($event, index)"
        >
          {{ part[0] }}
        </button>
        <input
          v-else
          :value="part"
          type="text"
          :class="{
            'mixed-input__text': true,
            'mixed-input__text--last': index === model.length - 1,
          }"
          dir="ltr"
          :data-mixed-input-index="index"
          :style="{width: textPartWidth(index)}"
          :aria-label="ariaLabel"
          :disabled="disabled"
          @input="handleTextInput($event, index)"
          @focus="rememberCursor($event, index)"
          @click="rememberCursor($event, index)"
          @keyup="rememberCursor($event, index)"
          @keydown="handleTextKeydown($event, index)"
        />
      </template>

      <span
        ref="textMeasure"
        class="mixed-input__text mixed-input__text-measure"
        aria-hidden="true"
      />
    </div>

    <slot name="error" />
  </div>
</template>

<style scoped lang="scss">
  .mixed-input {
    display: grid;
    gap: 10px;
  }

  .mixed-input__editor {
    align-items: center;
    background: var(--c-input-fill);
    border: var(--c-input-border-width) var(--c-input-border-style)
      var(--c-input-border-color);
    border-radius: var(--c-input-radius);
    box-shadow: var(--c-input-shadow);
    box-sizing: border-box;
    display: flex;
    flex: 1 1 auto;
    flex-wrap: wrap;
    gap: 0;
    min-height: var(--c-size-control-md);
    min-width: 0;
    padding-block: 0;
    padding-inline: var(--c-input-spacing-inline);
  }

  .mixed-input__editor--error {
    border-color: var(--c-color-danger-border-loud);
  }

  .mixed-input__text {
    background: transparent;
    border: 0;
    box-shadow: none;
    box-sizing: content-box;
    flex: 0 0 auto;
    font: inherit;
    max-width: 100%;
    margin-right: -18px;
    min-width: 0;
    padding: 3px 0;
  }

  .mixed-input__text:focus {
    outline: none;
  }

  .mixed-input__text--last {
    flex-grow: 1;
  }

  .mixed-input__text-measure {
    display: inline-block;
    left: -9999px;
    margin-right: 0;
    max-width: none;
    position: absolute;
    top: -9999px;
    visibility: hidden;
    white-space: pre;
  }

  .mixed-input__token {
    align-items: center;
    background: var(--c-color-neutral-fill-normal);
    border: 0;
    border-radius: var(--c-radius-sm);
    color: var(--c-color-neutral-on-normal);
    display: inline-flex;
    font-family: var(--c-font-mono);
    font-size: var(--c-text-sm);
    gap: 0.25rem;
    line-height: 1.3;
    padding: 0.125rem 0.4rem;
  }

  .mixed-input__token--editable {
    appearance: none;
    cursor: pointer;
  }

  .mixed-input__token--selected,
  .mixed-input__token--editable:focus {
    box-shadow: 0 0 0 1px var(--c-input-fill);
    outline: 2px solid var(--c-text-link);
    outline-offset: 1px;
  }
</style>
