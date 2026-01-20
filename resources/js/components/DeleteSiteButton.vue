<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {Deferred, useForm, usePage} from '@inertiajs/vue3';
  import ModalForm from '@/components/ModalForm.vue';
  import type {Site} from '@/types';
  import {computed, ref} from 'vue';
  import {destroy} from '@actions/Settings/SitesController';

  const props = withDefaults(
    defineProps<{
      site: Site;
    }>(),
    {}
  );

  const page = usePage<{
    transferContentOptions?: Array<Site>;
  }>();

  const modalActive = ref(false);
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
    form.clearErrors().delete(destroy(props.site.id).url, {
      onSuccess: () => {
        modalActive.value = false;
        form.reset();
      },
    });
  }

  function handleModalClose() {
    modalActive.value = false;
    form.clearErrors();
    form.reset();
  }
</script>

<template>
  <div>
    <div class="flex justify-end gap-2">
      <craft-button
        size="small"
        icon
        type="button"
        variant="danger"
        appearance="plain"
        :disabled="site.primary"
        @click="modalActive = true"
      >
        <craft-icon name="x" label="t('Delete site'"></craft-icon>
      </craft-button>
    </div>
    <ModalForm
      :is-active="modalActive"
      @close="handleModalClose"
      @submit="handleSubmit"
      :loading="form.processing"
      :submit-label="
        t('Delete {site}', {
          site: site.name,
        })
      "
    >
      <div class="grid gap-3">
        <!--        <p id="content-action">-->
        <!--          {{-->
        <!--            t(-->
        <!--              'What do you want to do with any content that is only available in {siteName}?',-->
        <!--              {siteName: site.name}-->
        <!--            )-->
        <!--          }}-->
        <!--        </p>-->

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
            <template #fallback> Loading ... </template>

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
                  v-for="site in transferContentOptions"
                  :key="site.id"
                  :selected="site.id === form.transferContentTo"
                  :value="site.id"
                >
                  {{ site.name }}
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
  </div>
</template>

<style scoped lang="scss"></style>
