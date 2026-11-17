<script lang="ts">
  /** A run of a message’s text, and whether it’s a mention. */
  export interface MentionSegment {
    text: string;
    mention: boolean;
  }

  /**
   * Splits a message’s text into runs, marking where each mention appears.
   * Longer mentions win where they overlap, and a mention that’s been edited
   * no longer matches.
   */
  export function mentionSegments(
    text: string,
    mentions: readonly string[]
  ): MentionSegment[] {
    const ranges: Array<[number, number]> = [];

    for (const mention of [...mentions].sort((a, b) => b.length - a.length)) {
      if (!mention) {
        continue;
      }

      for (
        let start = text.indexOf(mention);
        start !== -1;
        start = text.indexOf(mention, start + mention.length)
      ) {
        const end = start + mention.length;

        if (ranges.every(([from, to]) => end <= from || start >= to)) {
          ranges.push([start, end]);
        }
      }
    }

    ranges.sort(([a], [b]) => a - b);

    const segments: MentionSegment[] = [];
    let position = 0;

    for (const [start, end] of ranges) {
      if (start > position) {
        segments.push({text: text.slice(position, start), mention: false});
      }

      segments.push({text: text.slice(start, end), mention: true});
      position = end;
    }

    if (position < text.length) {
      segments.push({text: text.slice(position), mention: false});
    }

    return segments;
  }
</script>

<script setup lang="ts">
  /**
   * Tints the mentions in a textarea’s text, from a layer behind it that has
   * the textarea’s box and text metrics, so the textarea itself stays as it
   * is: its text, caret, selection, and spelling marks. It’s only visual;
   * the mentions’ context chips carry what they mean.
   */
  import {useEventListener, useResizeObserver} from '@vueuse/core';
  import {computed, nextTick, shallowRef, watch, type CSSProperties} from 'vue';

  const props = defineProps<{
    /** The textarea to sit behind. */
    target: HTMLTextAreaElement;
    /** What the layer is positioned within. */
    container: HTMLElement;
    text: string;
    mentions: readonly string[];
  }>();

  /** The textarea’s properties that decide where its text goes. */
  const COPIED_STYLES = [
    'boxSizing',
    'fontFamily',
    'fontSize',
    'fontStyle',
    'fontWeight',
    'letterSpacing',
    'lineHeight',
    'paddingBlockStart',
    'paddingBlockEnd',
    'paddingInlineStart',
    'paddingInlineEnd',
    'borderBlockStartWidth',
    'borderBlockEndWidth',
    'borderInlineStartWidth',
    'borderInlineEndWidth',
    'tabSize',
    'textIndent',
    'textTransform',
    'wordSpacing',
  ] as const;

  const layer = shallowRef<HTMLElement | null>(null);
  const box = shallowRef<CSSProperties>({});

  const segments = computed(() => mentionSegments(props.text, props.mentions));

  function place(): void {
    const style = getComputedStyle(props.target);
    const target = props.target.getBoundingClientRect();
    const container = props.container.getBoundingClientRect();
    const copied = Object.fromEntries(
      COPIED_STYLES.map((name) => [name, style[name]])
    );

    box.value = {
      ...copied,
      // From inside the container’s border, which absolute positioning starts at.
      insetBlockStart: `${target.top - container.top - props.container.clientTop}px`,
      insetInlineStart: `${target.left - container.left - props.container.clientLeft}px`,
      // Without the textarea’s scrollbar, if it has one, so lines wrap alike.
      width: `${props.target.clientWidth + parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth)}px`,
      height: `${target.height}px`,
    };

    syncScroll();
  }

  function syncScroll(): void {
    if (layer.value) {
      layer.value.scrollTop = props.target.scrollTop;
    }
  }

  useResizeObserver(() => props.target, place);
  useResizeObserver(() => props.container, place);
  useEventListener(() => props.target, 'scroll', syncScroll, {passive: true});

  // The textarea grows as text is added, after the text changes.
  watch(
    () => props.text,
    async () => {
      await nextTick();
      place();
    },
    {immediate: true}
  );
</script>

<template>
  <div ref="layer" class="assistant-mentions" aria-hidden="true" :style="box">
    <template v-for="(segment, index) in segments" :key="index"
      ><mark v-if="segment.mention" class="assistant-mentions__mention">{{
        segment.text
      }}</mark
      ><template v-else>{{ segment.text }}</template></template
    >&#8203;
  </div>
</template>

<style scoped>
  .assistant-mentions {
    position: absolute;
    overflow: hidden;
    border-style: solid;
    border-color: transparent;
    color: transparent;
    white-space: pre-wrap;
    overflow-wrap: break-word;
    pointer-events: none;
  }

  .assistant-mentions__mention {
    border-radius: var(--c-radius-sm);
    background-color: var(--c-color-accent-fill-quiet);
    box-shadow: 0 0 0 2px var(--c-color-accent-fill-quiet);
    color: transparent;
  }
</style>
