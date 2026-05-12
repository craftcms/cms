<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {Form, useHttp} from '@inertiajs/vue3';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import TwoFactorAuthenticationController from '@actions/Auth/TwoFactorAuthenticationController';
  import Pane from '@/components/Pane.vue';
  import TransitionShake from '@/components/TransitionShake.vue';
  import {ref} from 'vue';
  import type {LoginResponse} from '@/types/auth';

  const emit = defineEmits<{
    (e: 'success', response: LoginResponse): void;
    (e: 'error', error: any): void;
  }>();

  const props = defineProps<{
    action?: string;
    returnUrl?: string;
    inline?: boolean;
  }>();

  const http = useHttp<{code: string; redirect: string}, LoginResponse>({
    code: '',
    redirect: props.returnUrl ?? '',
  });
  const loading = ref(false);
  const shaking = ref(false);
  const error = ref<string | null>(null);

  async function handleInlineSubmit() {
    if (loading.value) return;

    loading.value = true;
    error.value = null;

    const url = props.action ?? TwoFactorAuthenticationController.verifyRecoveryCode['/admin/actions/auth/verify-recovery-code']().url;

    try {
      const response = await http.post(url);
      emit('success', response);
    } catch (e: any) {
      error.value = e?.response?.data?.message ?? t('Invalid recovery code.');
      emit('error', e);
      shaking.value = true;
      setTimeout(() => {
        shaking.value = false;
      }, 1500);
    } finally {
      loading.value = false;
    }
  }
</script>

<template>
  <template v-if="inline">
    <TransitionShake :shaking="shaking">
      <Pane appearance="raised">
        <form @submit.prevent="handleInlineSubmit()">
          <div class="flex gap-2 items-end">
            <CraftInput
              :label="t('Recovery Code')"
              id="recovery-code"
              name="code"
              class="w-full"
              v-model="http.code"
              :error="error ?? undefined"
              autocomplete="off"
            />
            <craft-button type="submit" :loading="loading" variant="primary">
              {{ t('Verify') }}
            </craft-button>
          </div>
        </form>
      </Pane>
    </TransitionShake>
  </template>
  <template v-else>
    <Form :action="action" #default="{processing, errors}" method="post">
      <div class="flex gap-2 items-end">
        <input
          type="hidden"
          name="redirect"
          :value="returnUrl"
          v-if="returnUrl"
        />
        <CraftInput
          :label="t('Recovery Code')"
          id="recovery-code"
          name="code"
          class="w-full"
          :error="errors.code"
        />
        <craft-button type="submit" :loading="processing" variant="primary">
          {{ t('Verify') }}
        </craft-button>
      </div>
    </Form>
  </template>
</template>

<style scoped lang="scss"></style>
