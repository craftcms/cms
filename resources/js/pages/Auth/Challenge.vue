<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AuthBase from '@/layout/AuthBase.vue';
  import TwoFactorForm from '@/components/Auth/TwoFactorForm.vue';
  import ActionMenu from '@/components/ActionMenu.vue';
  import {computed} from 'vue';
  import TwoFactorAuthenticationController from '@actions/Auth/TwoFactorAuthenticationController';
  import {useFlashMessages} from '@/composables/useFlashMessages';
  import {usePage} from '@inertiajs/vue3';

  const props = defineProps<{
    authFormData: {
      authMethod: string;
      otherMethods: Array<{
        name: string;
        class: string;
      }>;
      authForm: string;
      returnUrl: string;
    };
  }>();

  const page = usePage<{
    flash: {
      success: string | null;
      error: string | null;
    };
  }>();

  const actions = computed(() =>
    props.authFormData.otherMethods.map((method) => {
      return {
        href: TwoFactorAuthenticationController.showForm({
          query: {
            method: method.class,
          },
        }).url,
        label: method.name,
      };
    })
  );
</script>

<template>
  <AuthBase>
    <TwoFactorForm :auth-form-data="authFormData" />

    <template v-if="page.props.flash.error">
      {{ page.props.flash.error }}
    </template>

    <template v-if="actions.length > 0">
      <hr class="mt-4 mb-2">
      <ActionMenu :actions="actions" :label="t('Try another way')">
        <template #invoker="{label, attributes}">
          <craft-button
            type="button"
            appearance="plain"
            size="zero"
            v-bind="attributes"
          >
            {{ label }}
            <craft-icon name="chevron-down"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
    </template>
  </AuthBase>
</template>

<style scoped lang="scss"></style>
