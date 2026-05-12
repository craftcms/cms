<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import LoginController from '@actions/Auth/LoginController';
  import {useHttp} from '@inertiajs/vue3';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftInputPassword from '@craftcms/cp/vue/CraftInputPassword.vue';
  import CraftCheckbox from '@craftcms/cp/vue/CraftCheckbox.vue';
  import {computed, ref} from 'vue';
  import useCraftData from '@/composables/useCraftData';
  import {cpHumanizer} from '@craftcms/cp/utilities/format.ts.mjs';
  import Pane from '@/components/Pane.vue';
  import TransitionShake from '@/components/TransitionShake.vue';
  import type {LoginResponse} from '@/types/auth';

  const emit = defineEmits<{
    (e: 'success', response: LoginResponse): void;
    (e: 'error', error: any): void;
    (e: 'change:view', view: 'login' | 'set-password'): void;
  }>();

  const props = withDefaults(
    defineProps<{
      showPasswordReset?: boolean;
      showRememberCheckbox?: boolean;
      showUsername?: boolean;
      initialUsername?: string;
      usernameProps: {
        label: string;
        type: string;
      };
    }>(),
    {
      showPasswordReset: true,
      showRememberCheckbox: true,
      showUsername: true,
      initialUsername: '',
    }
  );

  const {general} = useCraftData();

  const http = useHttp<{loginName: string; password: string; rememberMe: boolean}, LoginResponse>({
    loginName: props.initialUsername,
    password: '',
    rememberMe: false,
  });

  const shaking = ref(false);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function handleSubmit() {
    if (loading.value) return;

    loading.value = true;
    error.value = null;

    try {
      const response = await http.post(LoginController.attemptLogin().url);
      emit('success', response);
    } catch (e: any) {
      error.value = e?.response?.data?.message ?? null;
      emit('error', e);
      shaking.value = true;
      setTimeout(() => {
        shaking.value = false;
      }, 1500);
    } finally {
      loading.value = false;
    }
  }

  const humanizedDuration = computed(() =>
    cpHumanizer(general.rememberedUserSessionDuration * 1000)
  );
</script>

<template>
  <TransitionShake :shaking="shaking">
    <Pane appearance="raised">
      <form @submit.prevent="handleSubmit()">
        <div class="grid gap-3">
          <template v-if="showUsername">
            <CraftInput
              :label="usernameProps.label"
              :type="usernameProps.type"
              name="username"
              autocomplete="username"
              :autocapitalize="false"
              :required="true"
              v-model="http.loginName"
              :error="error ?? undefined"
            />
          </template>
          <div>
            <CraftInputPassword
              name="password"
              label="Password"
              v-model="http.password"
              :required="true"
              autocomplete="current-password"
              :error="showUsername ? undefined : (error ?? undefined)"
            />
            <template v-if="showPasswordReset">
              <div class="mt-2">
                <craft-button
                  type="button"
                  appearance="none"
                  @click="emit('change:view', 'set-password')"
                  >{{ t('Forgot password?') }}</craft-button
                >
              </div>
            </template>
          </div>

          <template
            v-if="showRememberCheckbox && general.rememberedUserSessionDuration"
          >
            <CraftCheckbox
              :label="
                t('Stay signed in for {duration}', {
                  duration: humanizedDuration,
                })
              "
              v-model="http.rememberMe"
              name="rememberMe"
            />
          </template>
        </div>

        <div class="mt-4">
          <craft-button
            type="submit"
            variant="primary"
            :loading="loading"
            class="w-full"
            >{{ t('Sign in') }}</craft-button
          >
        </div>
      </form>
    </Pane>
  </TransitionShake>
</template>

<style scoped lang="scss"></style>
