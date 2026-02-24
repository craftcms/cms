<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts';
  import findAndReplaceController from '@actions/Utilities/FindAndReplaceController.ts';
  import {useForm} from '@inertiajs/vue3';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {useAnnouncer} from '@/composables/useAnnouncer';
  import Input from '@/components/form/Input.vue';

  const {announce} = useAnnouncer();

  const form = useForm({
    find: '',
    replace: '',
  });

  function submit() {
    form.clearErrors();
    form.submit(findAndReplaceController(), {
      onSuccess: () => {
        form.reset();
        announce(t('Success'));
      },
    });
  }
</script>

<template>
  <div class="p-4">
    <form @submit.prevent="submit" method="post">
      <div class="grid gap-3">
        <Input
          :label="t('Find Text')"
          v-model="form.find"
          name="find"
          :error="form.errors.find"
        />
        <Input
          :label="t('Replace Text')"
          v-model="form.replace"
          name="replace"
          :error="form.errors.replace"
        />
      </div>
      <div class="mt-4">
        <div class="flex gap-2 items-center">
          <craft-button
            type="submit"
            :loading="form.processing"
            variant="primary"
          >
            {{ t('Find and Replace') }}
          </craft-button>
          <TransitionFade>
            <template v-if="form.recentlySuccessful">
              <craft-callout
                variant="success"
                icon="circle-check"
                appearance="plain"
                class="p-0"
              >
                {{ t('Replace job dispatched.') }}
              </craft-callout>
            </template>
          </TransitionFade>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped lang="scss"></style>
