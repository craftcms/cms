<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import LoginController from '@actions/Auth/LoginController';
  import PermissionsController from '@actions/Users/PermissionsController';
  import PreferencesController from '@actions/Users/PreferencesController';
  import UsersController from '@actions/Users/UsersController';
  import useCraftData from '@/common/composables/useCraftData';
  import {computed} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CurrentUser from '@/common/components/CurrentUser.vue';
  import UserThumbnail from '@/common/components/UserThumbnail.vue';
  import PasswordController from '@actions/Users/PasswordController';

  const {currentUser} = useCraftData();

  const menuItems = computed(() => {
    return [
      {
        type: 'display',
        is: CurrentUser,
      },
      {type: 'hr'},
      {
        href: UsersController.edit['/admin/myaccount']().url,
        label: t('Profile'),
      },
      {
        href: PermissionsController.index['/admin/myaccount/permissions']().url,
        label: t('Permissions'),
      },
      {
        href: PreferencesController.index().url,
        label: t('Preferences'),
      },
      {
        href: PasswordController.index().url,
        label: t('Password & Verification'),
      },
      {type: 'hr'},
      {
        href: LoginController.logout().url,
        variant: 'danger',
        label: t('Sign out'),
      },
    ];
  });
</script>

<template>
  <ActionMenu :actions="menuItems" :label="currentUser!.username">
    <template #invoker>
      <craft-button
        slot="invoker"
        type="button"
        aria-label="User menu"
        appearance="none"
      >
        <UserThumbnail />
      </craft-button>
    </template>
  </ActionMenu>
</template>

<style scoped lang="css">
  @media screen and (min-width: 1024px) {
  }
</style>
