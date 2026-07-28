<script setup lang="ts">
  import {onBeforeUnmount, onMounted} from 'vue';
  import {t} from '@craftcms/ui';
  import {useForm, usePage} from '@inertiajs/vue3';
  import Pane from '@/common/components/Pane.vue';
  import CraftInputPassword from '@craftcms/ui/vue/CraftInputPassword.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import {store} from '@actions/Users/PasswordController';

  defineOptions({
    inheritAttrs: false,
  });

  const page = usePage<{
    authMethods: CraftCms.Cms.View.HtmlFragment | null;
  }>();

  interface PasswordForm {
    newPassword: string;
  }

  const form = useForm<PasswordForm>({
    newPassword: '',
  });

  const {save} = useSettingsSave(form, store, {
    passwordConfirmation: {
      // Changing a password always requires an elevated session (the save
      // action is behind `password.confirm`), but only when there's actually a
      // new password to save.
      required: (data) => Boolean(data.newPassword),
      // Re-request the full elevated-session window (capped at 5 minutes) so
      // there's time to finish before it lapses.
      minimumRemainingSeconds: 300,
    },
  });

  useAppLayout({form, onSave: save});

  // The Two-Step Verification listing is server-rendered HTML (shared with the
  // legacy 2FA screen). Boot the existing `AuthMethodSetup` module on it once
  // HtmlFragmentRenderer has injected the `#auth-method-setup` markup.
  let authMethodSetup: {destroy?: () => void} | null = null;

  function waitForElement(selector: string, maxFrames = 180): Promise<boolean> {
    return new Promise((resolve) => {
      let frames = 0;
      const check = () => {
        if (document.querySelector(selector)) {
          resolve(true);
        } else if (++frames > maxFrames) {
          resolve(false);
        } else {
          requestAnimationFrame(check);
        }
      };
      check();
    });
  }

  onMounted(async () => {
    const craft = (window as any).Craft;
    if (!craft?.AuthMethodSetup) {
      return;
    }

    if (await waitForElement('#auth-method-setup')) {
      authMethodSetup = new craft.AuthMethodSetup();
      craft.authMethodSetup = authMethodSetup;
    }
  });

  onBeforeUnmount(() => {
    authMethodSetup?.destroy?.();
    const craft = (window as any).Craft;
    if (craft && craft.authMethodSetup === authMethodSetup) {
      craft.authMethodSetup = null;
    }
  });
</script>

<template>
  <Pane appearance="raised" :padding="0">
    <div class="grid gap-6 p-4">
      <section class="grid gap-3">
        <h2 class="text-base">{{ t('Change your Password') }}</h2>

        <craft-field-group>
          <CraftInputPassword
            v-model="form.newPassword"
            :label="t('New Password')"
            id="newPassword"
            name="newPassword"
            autocomplete="new-password"
            :error="form.errors.newPassword"
          />
        </craft-field-group>
      </section>

      <hr />

      <section class="grid gap-3">
        <h2 class="text-base">{{ t('Two-Step Verification') }}</h2>
        <p>
          {{
            t(
              'Improve your account’s security by adding a second verification step when signing in.'
            )
          }}
        </p>

        <HtmlFragmentRenderer :fragment="page.props.authMethods" />
      </section>
    </div>
  </Pane>
</template>
