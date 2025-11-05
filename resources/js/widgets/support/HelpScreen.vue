<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
  import {t} from '@craftcms/cp';
  import {computed} from 'vue';
  import {useAxios} from '@/composables/useAxios';
  import SimilarItems from '@/widgets/support/SimilarItems.vue';
  import SimilarItem from '@/widgets/support/SimilarItem.vue';

  interface SOAnswer {
    title: string;
    link: string;
    is_answered: boolean;
  }

  const {app} = useCraftData();

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

  const params = computed(() => {
    return {
      site: 'craftcms',
      sort: 'relevance',
      order: 'desc',
      pagesize: 20,
      title: bodyProxy.value,
    };
  });

  const {data, state, isLoading} = useAxios<{
    items: Array<SOAnswer>;
  }>('https://api.stackexchange.com/2.3/similar', {
    params,
    enabled,
    debounce: 500,
  });

  const similarQuestions = computed(() => data.value?.items);
</script>

<template>
  <form
    action="https://craftcms.stackexchange.com/questions/ask"
    target="_blank"
    rel="noopener noreferrer"
  >
    <craft-textarea
      name="title"
      max-rows="10"
      :label="t('app', 'Briefly describe your question.')"
      rows="5"
      autofocus
      v-model="bodyProxy"
    ></craft-textarea>

    <SimilarItems
      :items="similarQuestions"
      :state="state"
      :title="t('app', 'Similar questions on Stack Exchange')"
      skip-to-id="help-support-footer"
    >
      <template v-slot:item="{item}">
        <SimilarItem
          :link="item.link"
          :status="item.is_answered ? 'success' : 'neutral'"
        >
          {{ item.title }}
        </SimilarItem>
      </template>
    </SimilarItems>

    <div class="tw:flex tw:gap-2 tw:mt-4 tw:justify-end tw:items-center" id="help-support-footer">
      <craft-spinner
        v-if="similarQuestions?.length && isLoading"
      ></craft-spinner>
      <div class="tw:ml-auto"></div>
      <craft-button type="reset" @click="emit('click:cancel')">{{
        t('app', 'Cancel')
      }}</craft-button>
      <craft-button type="submit" variant="primary">{{
        t('app', 'Ask on Stack Exchange')
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
