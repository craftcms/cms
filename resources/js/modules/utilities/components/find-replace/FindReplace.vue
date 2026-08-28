<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import findAndReplaceController from '@actions/Utilities/FindAndReplaceController';
  import {useForm} from '@inertiajs/vue3';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import InlineFlash from '@/common/components/InlineFlash.vue';

  const form = useForm({
    find: '',
    replace: '',
  });

  function submit() {
    form.clearErrors();
    form.submit(findAndReplaceController(), {
      onSuccess: () => {
        form.reset();
      },
    });
  }
</script>

<template>
  <div class="cp:p-4">
    <form @submit.prevent="submit" method="post">
      <div class="cp:grid cp:gap-3">
        <CraftInput
          :label="t('Find Text')"
          v-model="form.find"
          name="find"
          :error="form.errors.find"
        />
        <CraftInput
          :label="t('Replace Text')"
          v-model="form.replace"
          name="replace"
          :error="form.errors.replace"
        />
      </div>
      <div class="cp:mt-4">
        <div class="cp:flex cp:gap-2 cp:items-center">
          <craft-button
            type="submit"
            :loading="form.processing"
            variant="accent"
          >
            {{ t('Find and Replace') }}
          </craft-button>
          <InlineFlash :is-active="form.recentlySuccessful" />
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped lang="scss"></style>
