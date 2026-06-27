<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/cp';
  import {useForm} from '@inertiajs/vue3';
  import AuthBase from '@/common/layouts/AuthBase.vue';
  import Pane from '@/common/components/Pane.vue';
  import CraftInputPassword from '@craftcms/cp/vue/CraftInputPassword.vue';
  import {store} from '@actions/Auth/SetPasswordController';

  interface SetPasswordForm {
    uid: string;
    code: string;
    newPassword: string;
  }

  const props = defineProps<{
    uid: string;
    code: string;
    newUser: boolean;
  }>();

  const form = useForm<SetPasswordForm>({
    uid: props.uid,
    code: props.code,
    newPassword: '',
  });

  const title = computed(() =>
    props.newUser ? t('Set Your Password') : t('Set Your New Password')
  );

  const passwordLabel = computed(() =>
    props.newUser ? t('Choose a password') : t('Choose a new password')
  );

  function submit() {
    form.post(store().url);
  }
</script>

<template>
  <AuthBase :title="title">
    <form @submit.prevent="submit">
      <Pane appearance="raised">
        <div class="grid gap-3">
          <CraftInputPassword
            id="newPassword"
            name="newPassword"
            v-model="form.newPassword"
            :label="passwordLabel"
            :error="form.errors.newPassword"
            autocomplete="new-password"
            required
            autofocus
          />

          <craft-button
            type="submit"
            variant="accent"
            :loading="form.processing"
            class="w-full"
          >
            {{ t('Set Password') }}
          </craft-button>
        </div>
      </Pane>
    </form>
  </AuthBase>
</template>
