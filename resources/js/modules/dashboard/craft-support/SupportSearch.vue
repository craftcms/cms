<script setup lang="ts">
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {computed, nextTick, ref, watch} from 'vue';
  import {watchDebounced} from '@vueuse/core';
  import {t} from '@craftcms/ui';
  import CraftTextarea from '@craftcms/ui/vue/CraftTextarea.vue';
  import type {SupportData} from './types';

  const {announce} = useAnnouncer();

  const props = defineProps<{
    active: boolean;
    screen: 'home' | 'help' | 'feedback';
    data: SupportData;
  }>();

  const emit = defineEmits<{contactSupport: []}>();
  const message = defineModel<string>({required: true});
  const searchForm = ref<HTMLFormElement>();
  const textarea = ref<InstanceType<typeof CraftTextarea>>();

  watch(
    () => props.active,
    async (active) => {
      if (active) {
        await nextTick();
        textarea.value?.$el.focus();
      }
    }
  );

  const results = ref<
    Array<{
      url: string;
      title: string;
      status: 'enabled' | 'pending' | 'expired';
      label: string;
    }>
  >([]);
  const search = computed(() =>
    props.screen === 'help'
      ? {
          heading: t('Ask on Stack Exchange'),
          prompt: t('Briefly describe your question.'),
          resultsHeading: t('Similar questions on Stack Exchange'),
          target: 'https://craftcms.stackexchange.com/questions/ask',
          params: {title: message.value},
        }
      : {
          heading: t('Post on GitHub'),
          prompt: t('Briefly describe your issue or idea.'),
          resultsHeading: t('Similar issues on GitHub'),
          target: 'https://github.com/craftcms/cms/issues/new',
          params: {
            ...props.data.issueParams,
            title: props.data.issueTitlePrefix + message.value,
          },
        }
  );

  watchDebounced(
    [message, () => props.screen, () => props.active],
    async (_, __, onCleanup) => {
      results.value = [];
      if (!message.value || !props.active) return;

      const abort = new AbortController();
      onCleanup(() => abort.abort());

      const query = message.value;
      const help = props.screen === 'help';
      const url = help
        ? 'https://api.stackexchange.com/2.2/similar?site=craftcms&sort=relevance&order=desc&title=' +
          encodeURIComponent(query)
        : 'https://api.github.com/search/issues?q=type:issue+repo:craftcms/cms+' +
          encodeURIComponent(query);

      try {
        const response = await fetch(url, {signal: abort.signal});
        if (!response.ok) throw new Error('Search failed.');

        const data = await response.json();
        results.value = (data.items ?? [])
          .slice(0, 20)
          .map(
            (item: {
              link: string;
              html_url: string;
              title: string;
              is_answered: boolean;
              state: string;
            }) => {
              const positive = help ? item.is_answered : item.state === 'open';

              return {
                url: help ? item.link : item.html_url,
                title: item.title,
                status: positive ? 'enabled' : help ? 'pending' : 'expired',
                label: help
                  ? positive
                    ? t('Answered')
                    : t('Unanswered')
                  : positive
                    ? t('Open')
                    : t('Closed'),
              };
            }
          )
          .filter((item: {url: string}) => /^https?:\/\//i.test(item.url));

        if (results.value.length)
          announce(
            t('Showing results for “{searchQuery}”', {searchQuery: query})
          );
      } catch {
        results.value = [];
      }
    },
    {debounce: 500}
  );

  function submit() {
    if (message.value) searchForm.value?.requestSubmit();
  }
</script>

<template>
  <div class="space-y-4">
    <h2 class="text-lg">{{ search.heading }}</h2>
    <CraftTextarea
      ref="textarea"
      v-model="message"
      :label="search.prompt"
      :rows="5"
      @keydown.enter.ctrl.prevent="submit"
      @keydown.enter.meta.prevent="submit"
    />
    <div v-if="results.length">
      <h3>{{ search.resultsHeading }}</h3>
      <ul class="space-y-2">
        <li v-for="result in results" :key="result.url">
          <craft-status
            :status="result.status"
            :label="result.label"
          ></craft-status
          ><a :href="result.url" target="_blank" rel="noopener">{{
            result.title
          }}</a>
        </li>
      </ul>
    </div>
    <form
      ref="searchForm"
      :action="search.target"
      method="get"
      target="_blank"
      rel="noopener"
    >
      <input
        v-for="(value, key) in search.params"
        :key="key"
        type="hidden"
        :name="key"
        :value="value"
      />
      <craft-button type="submit" variant="primary" :disabled="!message">{{
        search.heading
      }}</craft-button>
    </form>
    <craft-button
      v-if="data.canContactSupport"
      type="button"
      @click="emit('contactSupport')"
      >{{ t('Contact Developer Support') }}</craft-button
    >
    <h3>{{ t('More Resources') }}</h3>
    <ul class="space-y-2">
      <li v-for="{url, label} in data.resources" :key="url">
        <a :href="url" target="_blank" rel="noopener">{{ label }}</a>
      </li>
    </ul>
  </div>
</template>
