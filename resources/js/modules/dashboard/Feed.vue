<script setup lang="ts">
  import {computed, onMounted, onBeforeUnmount, ref, useTemplateRef} from 'vue';
  import {useResizeObserver} from '@vueuse/core';
  import {actionClient, t} from '@craftcms/ui';
  import {cacheData} from '@actions/Dashboard/Widgets/FeedController';
  import {useWidgetColor} from './useWidgetColor';

  const color = useWidgetColor();

  interface FeedData {
    direction?: string;
    items?: Array<{
      permalink: string;
      title: string;
      date?: string;
      // Missing from feeds cached before the proxy started sending them.
      summary?: unknown;
      image?: unknown;
    }>;
  }

  const props = defineProps<{
    data: {
      url: string;
      limit: number;
      feed: FeedData | null;
      formattingLocale: string;
      /** The user's time zone, which dates are shown in. */
      timeZone?: string;
    };
  }>();

  const formatOptions: Intl.DateTimeFormatOptions = {
    calendar: 'gregory',
    numberingSystem: 'latn',
    timeZone: props.data.timeZone,
  };
  const dateFormatter = new Intl.DateTimeFormat(props.data.formattingLocale, {
    ...formatOptions,
    dateStyle: 'medium',
  });
  const timeFormatter = new Intl.DateTimeFormat(props.data.formattingLocale, {
    ...formatOptions,
    timeStyle: 'medium',
  });
  const weekdayFormatter = new Intl.DateTimeFormat(
    props.data.formattingLocale,
    {...formatOptions, weekday: 'long'}
  );
  // Calendar days in the user's time zone, as YYYY-MM-DD.
  const dayFormatter = new Intl.DateTimeFormat('en-CA', {
    timeZone: props.data.timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });

  const DAY = 24 * 60 * 60 * 1000;

  function calendarDaysAgo(date: Date, now: Date): number {
    return Math.round(
      (Date.parse(dayFormatter.format(now)) -
        Date.parse(dayFormatter.format(date))) /
        DAY
    );
  }

  /**
   * Formats a date as the server's `asTimestamp()` does at the medium length:
   * the time today, “Yesterday”, the weekday within the past week, or else the
   * date.
   */
  function formatDate(value: string) {
    const date = new Date(value);
    const now = new Date();
    const daysAgo = calendarDaysAgo(date, now);

    if (daysAgo === 0) {
      return timeFormatter.format(date);
    }

    if (daysAgo === 1) {
      return t('Yesterday');
    }

    if (daysAgo > 1 && now.getTime() - date.getTime() <= 7 * DAY) {
      return weekdayFormatter.format(date);
    }

    return dateFormatter.format(date);
  }

  const feed = ref(props.data.feed);
  const error = ref('');
  const abort = new AbortController();

  const isHttpUrl = (value: unknown): value is string =>
    typeof value === 'string' && /^https?:\/\//i.test(value);

  function summaryFor(item: {summary?: unknown}): string | null {
    return typeof item.summary === 'string' && item.summary.trim()
      ? item.summary.trim()
      : null;
  }

  const items = computed(() =>
    (feed.value?.items ?? [])
      .filter((item) => isHttpUrl(item.permalink))
      .slice(0, props.data.limit)
  );

  const failedImages = ref(new Set<string>());

  function imageFor(item: {image?: unknown}): string | null {
    return isHttpUrl(item.image) && !failedImages.value.has(item.image)
      ? item.image
      : null;
  }

  function onImageError(src: string) {
    failedImages.value = new Set(failedImages.value).add(src);
  }

  onBeforeUnmount(() => abort.abort());

  /**
   * Each item's title and summary share four lines: titles take up to two,
   * and summaries the rest. CSS can't clamp across both once the title is
   * clamped too, so the titles' line counts are measured.
   */
  const LINES = 4;
  const MAX_TITLE_LINES = 2;
  const titles = useTemplateRef<HTMLElement[]>('titles');
  const titleLines = ref<Record<string, number>>({});

  function lineCount(element: HTMLElement): number {
    const lineHeight = parseFloat(getComputedStyle(element).lineHeight);

    return lineHeight > 0
      ? Math.max(1, Math.round(element.offsetHeight / lineHeight))
      : 1;
  }

  useResizeObserver(
    () => titles.value ?? [],
    (entries) => {
      const next = {...titleLines.value};

      for (const {target} of entries) {
        const permalink = (target as HTMLElement).dataset.permalink;

        if (permalink) {
          next[permalink] = lineCount(target as HTMLElement);
        }
      }

      titleLines.value = next;
    }
  );

  function summaryLines(item: {permalink: string}): number {
    return (
      LINES - Math.min(titleLines.value[item.permalink] ?? 1, MAX_TITLE_LINES)
    );
  }

  /** Whether the feed was cached before the proxy sent summaries and images. */
  const predatesDetails = (data: FeedData | null): boolean =>
    !!data?.items?.length &&
    data.items.every((item) => !('summary' in item) && !('image' in item));

  onMounted(async () => {
    const stale = predatesDetails(feed.value);

    if (feed.value && !stale) return;

    try {
      const url = new URL('https://feed-proxy.craftcms.com/');
      url.searchParams.set('url', props.data.url);

      const response = await fetch(url, {signal: abort.signal});
      if (!response.ok) throw new Error('Feed request failed.');

      feed.value = await response.json();

      await actionClient.post(cacheData.url(), {
        url: props.data.url,
        data: feed.value,
      });
    } catch {
      // A cached feed is still worth showing if refreshing it fails.
      if (!abort.signal.aborted && !stale) {
        error.value = t('Could not load the feed');
      }
    }
  });
</script>

<template>
  <craft-card :data-color="color">
    <slot name="header" />
    <div class="body">
      <craft-callout v-if="error" variant="danger" role="alert">{{
        error
      }}</craft-callout>
      <ol
        v-else-if="feed"
        :dir="feed.direction === 'rtl' ? 'rtl' : 'ltr'"
        class="feed-items"
      >
        <li v-for="item in items" :key="item.permalink" class="feed-item">
          <div class="feed-item__text">
            <a
              :href="item.permalink"
              target="_blank"
              rel="noopener"
              class="feed-item__title"
              ref="titles"
              :data-permalink="item.permalink"
              >{{ item.title }}</a
            >
            <p
              v-if="item.date || summaryFor(item)"
              class="feed-item__summary"
              :style="{'--_summary-lines': summaryLines(item)}"
            >
              <time v-if="item.date" :datetime="item.date">{{
                formatDate(item.date)
              }}</time>
              <template v-if="item.date && summaryFor(item)">
                <span class="feed-item__separator" aria-hidden="true">·</span
                ><span class="sr-only">, </span></template
              >{{ summaryFor(item) }}
            </p>
          </div>
          <img
            v-if="imageFor(item)"
            class="feed-item__image"
            :src="imageFor(item)!"
            alt=""
            width="80"
            height="80"
            loading="lazy"
            referrerpolicy="no-referrer"
            @error="onImageError(imageFor(item)!)"
          />
        </li>
      </ol>
      <craft-spinner v-else visible role="status">{{
        t('Loading…')
      }}</craft-spinner>
    </div>
  </craft-card>
</template>

<style scoped>
  /* Spaced like the Recent Entries and My Drafts widgets' items. */
  .feed-items {
    display: grid;
    gap: calc(var(--c-spacing) * 3);
  }

  .feed-item {
    display: flex;
    align-items: flex-start;
    gap: var(--c-spacing-md);
  }

  .feed-item__text {
    flex: 1;
    min-width: 0;
  }

  .feed-item__title {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    overflow: hidden;
  }

  .feed-item__summary {
    margin-block: var(--c-spacing-xs) 0;
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
    white-space: pre-line;
    /* The lines its title leaves (see summaryLines()). */
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: var(--_summary-lines, 3);
    line-clamp: var(--_summary-lines, 3);
    overflow: hidden;
  }

  .feed-item__separator {
    margin-inline: var(--c-spacing-sm);
  }

  .feed-item__image {
    flex: none;
    inline-size: calc(80rem / 16);
    block-size: calc(80rem / 16);
    object-fit: cover;
    border-radius: var(--c-radius-sm);
  }
</style>
