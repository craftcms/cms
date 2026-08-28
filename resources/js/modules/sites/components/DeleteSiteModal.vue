<script setup lang="ts">
  import {Deferred, useForm, usePage} from '@inertiajs/vue3';
  import ModalForm from '@/common/components/ModalForm.vue';
  import {computed} from 'vue';
  import {destroy} from '@actions/Settings/SitesController';
  import type {Site} from '@/common/types';
  import {t} from '@craftcms/ui';

  const emit = defineEmits<{
    (e: 'close'): void;
  }>();
  const props = withDefaults(
    defineProps<{
      open?: boolean;
      site: Site;
    }>(),
    {open: false}
  );

  const page = usePage<{
    transferContentOptions?: Array<Site>;
  }>();
  const transferContentOptions = computed(() => {
    if (page.props.transferContentOptions) {
      return page.props.transferContentOptions.filter(
        (site) => site.id !== props.site.id
      );
    }

    return [];
  });

  const form = useForm({
    id: props.site.id,
    contentDestination: 'transfer',
    transferContentTo: null,
  });

  async function handleSubmit() {
    deleteSite();
  }

  function deleteSite() {
    form.clearErrors().delete(destroy({site: props.site.id}).url, {
      onSuccess: () => {
        emit('close');
        form.reset();
      },
    });
  }

  function handleModalClose() {
    emit('close');
    form.clearErrors();
    form.reset();
  }
</script>

<template>
  <ModalForm
    :title="t('Delete {site}', {site: site.name})"
    :is-active="open"
    @close="handleModalClose"
    @submit="handleSubmit"
    :loading="form.processing"
    :submit-label="t('Delete')"
  >
    <div class="cp:grid cp:gap-3">
      <craft-radio-group
        name="contentDestination"
        :label="t('Content Destination')"
        :help-text="
          t(
            'What do you want to do with any content that is only available in {siteName}?',
            {siteName: site.name}
          )
        "
        .modelValue="form.contentDestination"
        @model-value-changed="
          form.contentDestination = $event.target.modelValue
        "
      >
        <craft-radio
          :label="t('Transfer it')"
          .choiceValue="'transfer'"
          :checked="'transfer' === form.contentDestination"
        >
        </craft-radio>
        <craft-radio
          :label="t('Delete it')"
          .choiceValue="'delete'"
          :checked="'delete' === form.contentDestination"
        ></craft-radio>
      </craft-radio-group>

      <template v-if="form.contentDestination === 'transfer'">
        <Deferred data="transferContentOptions">
          <template #fallback>
            <craft-input disabled :label="t('Transfer content to')">
            </craft-input>
          </template>

          <craft-select
            :label="t('Transfer content to')"
            id="transfer-to"
            name="transferContentTo"
            .modelValue="form.transferContentTo"
            @model-value-changed="
              form.transferContentTo = $event.target.modelValue
            "
          >
            <select slot="input">
              <option value="">
                {{ t('Select site') }}
              </option>
              <option
                v-for="siteOption in transferContentOptions"
                :key="siteOption.id"
                :selected="siteOption.id === form.transferContentTo"
                :value="siteOption.id"
              >
                {{ siteOption.name }}
              </option>
            </select>

            <div slot="feedback">
              <ul class="error-list" v-if="form.errors?.transferContentTo">
                <li>{{ form.errors.transferContentTo }}</li>
              </ul>
            </div>
          </craft-select>
        </Deferred>
      </template>
    </div>
  </ModalForm>
</template>

<style scoped lang="scss"></style>
