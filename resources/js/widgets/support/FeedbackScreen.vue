<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
  import {t} from '@craftcms/cp';
  import {computed, inject} from 'vue';
  import SimilarItems from '@/widgets/support/SimilarItems.vue';
  import SimilarItem from '@/widgets/support/SimilarItem.vue';
  import {useAxios} from '@/composables/useAxios';

  interface GHIssue {
    html_url: string;
    state: string;
    title: string;
  }

  const {app} = useCraftData();
  const inputParams = inject('support-widget:issue-params');
  const issueTitlePrefix = inject('support-widget:issue-title-prefix');

  const emit = defineEmits<{
    (e: 'click:support'): void;
    (e: 'click:cancel'): void;
    (e: 'dialog:hide'): void;
    (e: 'update:model-value', value: string): void;
  }>();

  const props = withDefaults(
    defineProps<{
      modelValue?: string;
    }>(),
    {modelValue: ''}
  );

  const bodyProxy = computed({
    get() {
      return props.modelValue;
    },
    set(newValue) {
      emit('update:model-value', newValue);
    },
  });

  const enabled = computed(() => bodyProxy.value.length > 10);

  const url = computed(() => {
    return `https://api.github.com/search/issues?q=type:issue+repo:craftcms/cms+${encodeURIComponent(
      bodyProxy.value
    )}`;
  });

  const {data, state, isLoading} = useAxios<{items: Array<GHIssue>}>(url, {
    enabled,
    debounce: 500,
  });

  const issues = computed(() => data.value?.items);
</script>

<template>
  <form
    action="https://github.com/craftcms/cms/issues/new"
    target="_blank"
    rel="noopener noreferrer"
  >
    <template v-for="(value, name) in inputParams" :key="name">
      <input type="hidden" :value="value" :name="name" />
    </template>

    <input type="hidden" name="title" :value="`${issueTitlePrefix}${bodyProxy}`">

    <craft-textarea
      max-rows="10"
      :label="t('app', 'Briefly describe your issue or idea.')"
      rows="5"
      autofocus
      v-model="bodyProxy"
    ></craft-textarea>

    <SimilarItems
      :items="issues"
      :state="state"
      :title="t('app', 'Similar issues on GitHub')"
      skip-to-id="feedback-support-footer"
    >
      <template v-slot:item="{item}">
        <SimilarItem
          :link="item.html_url"
          :status="item.state === 'open' ? 'success' : 'error'"
        >
          {{ item.title }}
        </SimilarItem>
      </template>
    </SimilarItems>

    <div class="tw:flex tw:gap-2 tw:mt-4 tw:justify-end" id="feedback-support-footer">
      <craft-button type="reset" @click="emit('click:cancel')">{{
        t('app', 'Cancel')
      }}</craft-button>
      <craft-button type="submit" variant="primary">{{
        t('app', 'Post on GitHub')
      }}</craft-button>
    </div>
    <template v-if="app.edition === 'Pro'">
      <div class="tw:mt-3 tw:text-center">
        or
        <button
          type="button"
          @click.prevent="emit('click:support')"
          class="tw:text-blue-600"
        >
          send to Developer Support
        </button>
      </div>
    </template>
  </form>
</template>

<style scoped lang="scss"></style>
