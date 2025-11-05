<script setup lang="ts">
  import useCraftData, {useHelpers} from '@/composables/useCraftData';
  import {t} from '@craftcms/cp';
  import {computed, inject, ref} from 'vue';
  import axios from 'axios';

  const {getActionUrl} = useHelpers();
  const {currentUser} = useCraftData();
  const state = ref<string>('idle');
  const errors = ref<Record<string, Array<string>> | null>(null);
  const response = ref<any>(null);

  /**
   * @TODO This should probably be a composable
   * @param event
   */
  async function handleSubmit(event: SubmitEvent) {
    if (state.value === 'loading') {
      return;
    }

    state.value = 'loading';
    const target = event.target as HTMLFormElement;
    const formData = new FormData(target);

    try {
      response.value = await axios.post(target.action, formData, {
        headers: {
          Accept: 'application/json',
        },
      });
      state.value = 'success';
    } catch (error) {
      state.value = 'error';
      if (axios.isAxiosError(error)) {
        errors.value = error.response?.data?.errors;
      }
    }
  }

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
    set(newValue: string) {
      emit('update:model-value', newValue);
    },
  });

  const widgetId = inject('support-widget:widget-id');
  const issueParams = inject('support-widget:issue-params');
</script>

<template>
  <form
    :action="getActionUrl('dashboard/send-support-request')"
    enctype="multipart/form-data"
    @submit.prevent="handleSubmit"
  >
    <ul
      v-if="errors?.form"
      class="tw:text-red-700 tw:bg-red-50 tw:p-2 tw:border tw:border-red-400 tw:rounded-md tw:mb-4"
    >
      <template v-for="error in errors?.form">
        <li>
          {{ error }}
        </li>
      </template>
    </ul>

    <input type="hidden" name="widgetId" :value="widgetId" />

    <template v-for="(value, name) in issueParams" :key="name">
      <input type="hidden" :value="value" :name="name" />
    </template>

    <div class="tw:grid tw:gap-3">
      <craft-textarea
        name="message"
        max-rows="10"
        :label="t('app', 'Briefly describe your issue or idea.')"
        rows="5"
        autofocus
        v-model="bodyProxy"
      >
        <ul slot="after" v-if="errors?.message" class="tw:text-red-600">
          <template v-for="error in errors?.message">
            <li>
              {{ error }}
            </li>
          </template>
        </ul>
      </craft-textarea>

      <craft-input
        :label="t('app', 'Your Email')"
        name="fromEmail"
        :value="currentUser.email"
      ></craft-input>

      <craft-accordion>
        <div slot="invoker">
          <craft-button appearance="plain" size="small">
            <craft-icon name="chevron-down"></craft-icon>
            {{ t('app', 'More') }}
          </craft-button>
        </div>

        <div slot="content" class="tw:mt-3">
          <craft-checkbox
            :label="t('app', 'Attach error logs')"
            name="attachLogs"
            checked
            .choiceValue="1"
          ></craft-checkbox>
          <craft-checkbox
            :label="t('app', 'Attach a database backup')"
            name="attachDbBackup"
            checked
            .choiceValue="1"
          ></craft-checkbox>
          <craft-checkbox
            :label="t('app', 'Include your template files')"
            name="attachTemplates"
            checked
            .choiceValue="1"
          ></craft-checkbox>
        </div>
      </craft-accordion>

      <craft-input
        type="file"
        :label="t('app', 'Attach an additional file')"
      ></craft-input>
    </div>

    <div class="tw:flex tw:gap-2 tw:mt-4 tw:justify-end">
      <craft-button type="reset" @click="emit('click:cancel')">{{
        t('app', 'Cancel')
      }}</craft-button>
      <craft-button
        type="submit"
        :loading="state === 'loading'"
        variant="primary"
      >
        {{ t('app', 'Send') }}
      </craft-button>
    </div>
  </form>
</template>

<style scoped lang="scss"></style>
