<script setup lang="ts">
  import {computed, onMounted, onBeforeUnmount, ref} from 'vue';
  import {actionClient, t} from '@craftcms/ui';
  import {cacheData} from '@actions/Dashboard/Widgets/FeedController';

  interface FeedData {
    direction?: string;
    items?: Array<{permalink: string; title: string; date?: string}>;
  }

  const props = defineProps<{
    data: {
      url: string;
      limit: number;
      feed: FeedData | null;
      formattingLocale: string;
    };
  }>();

  const dateFormatter = new Intl.DateTimeFormat(props.data.formattingLocale, {
    dateStyle: 'short',
    calendar: 'gregory',
    numberingSystem: 'latn',
  });

  function formatDate(value: string) {
    const date = new Date(value);

    return dateFormatter
      .formatToParts(date)
      .map((part) =>
        part.type === 'year' ? String(date.getFullYear()) : part.value
      )
      .join('');
  }

  const feed = ref(props.data.feed);
  const error = ref('');
  const abort = new AbortController();

  const items = computed(() =>
    (feed.value?.items ?? [])
      .filter((item) => /^https?:\/\//i.test(item.permalink))
      .slice(0, props.data.limit)
  );

  onBeforeUnmount(() => abort.abort());

  onMounted(async () => {
    if (feed.value) return;

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
      if (!abort.signal.aborted) error.value = t('Could not load the feed');
    }
  });
</script>

<template>
  <craft-pane appearance="raised" padding="lg">
    <slot name="header" />
    <div class="body">
      <craft-callout v-if="error" variant="danger" role="alert">{{
        error
      }}</craft-callout>
      <ol
        v-else-if="feed"
        :dir="feed.direction === 'rtl' ? 'rtl' : 'ltr'"
        class="m-0 space-y-3"
      >
        <li v-for="item in items" :key="item.permalink">
          <a :href="item.permalink" target="_blank" rel="noopener">{{
            item.title
          }}</a>
          <span v-if="item.date" class="light nowrap ms-1">
            {{ formatDate(item.date) }}</span
          >
        </li>
      </ol>
      <craft-spinner v-else visible role="status">{{
        t('Loading…')
      }}</craft-spinner>
    </div>
  </craft-pane>
</template>
