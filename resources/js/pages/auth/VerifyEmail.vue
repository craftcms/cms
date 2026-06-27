<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {useForm} from '@inertiajs/vue3';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import Pane from '@/common/components/Pane.vue';
  import {store} from '@actions/Auth/VerifyEmailController';

  interface VerifyEmailForm {
    uid: string;
    code: string;
  }

  const props = defineProps<{
    uid: string;
    code: string;
  }>();

  const form = useForm<VerifyEmailForm>({
    uid: props.uid,
    code: props.code,
  });

  function submit() {
    form.post(store().url);
  }
</script>

<template>
  <AuthBase :title="t('Verify your email address')">
    <form @submit.prevent="submit">
      <Pane appearance="raised">
        <div class="grid gap-3">
          <h2 class="text-base">
            {{ t('Verify your email address') }}
          </h2>

          <craft-button
            type="submit"
            variant="accent"
            :loading="form.processing"
            class="w-full"
          >
            {{ t('Verify') }}
          </craft-button>
        </div>
      </Pane>
    </form>
  </AuthBase>
</template>
