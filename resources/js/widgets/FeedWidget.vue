<script setup lang="ts">
  import {useAxios} from '@/composables/useAxios';
  import axios from 'axios';
  import {useHelpers} from '@/composables/useCraftData';

  interface FeedItem {
    permalink?: string;
    title: string;
    date?: string;
  }

  const props = withDefaults(
    defineProps<{
      url?: string;
      direction?: 'ltr' | 'rtl';
      itemsData?: string;
    }>(),
    {direction: 'ltr', itemsData: '[]', url: ''}
  );

  const {getActionUrl} = useHelpers();
  const {data} = useAxios<{items: FeedItem[]}>(
    'https://feed-proxy.craftcms.com/',
    {
      params: {url: props.url},
      enabled: !!props.url,
      initialData: {
        items: JSON.parse(props.itemsData),
      },
      async onSuccess(data) {
        // Cache the data
        await axios.post(getActionUrl('dashboard/cache-feed-data'), {
          url: props.url,
          data: data,
        });
      },
    }
  );
</script>

<template>
  <ol dir="{{ feed.direction }}" v-if="data">
    <li class="tw:py-0.5" v-for="item in data.items" :key="item.title">
      <a :href="item.permalink" v-if="item.permalink">{{ item.title }}</a>
      <span v-else>{{ item.title }}</span
      >&nbsp;
      <relative-time v-if="item.date" :datetime="item.date" format="datetime">
      </relative-time>
    </li>
  </ol>
</template>

<style scoped lang="scss"></style>
