<script setup lang="ts">
    import {t} from '@craftcms/ui';
    import {logout} from '@routes/cp';
    import PermissionsController from '@actions/Users/PermissionsController';
    import PreferencesController from '@actions/Users/PreferencesController';
    import UsersController from '@actions/Users/UsersController';
    import useCraftData from '@/common/composables/useCraftData';
    import {computed, useTemplateRef} from 'vue';
    import ActionMenu from '@/common/components/ActionMenu.vue';
    import type {ActionItem} from '@/common/types';
    import CurrentUser from '@/common/components/CurrentUser.vue';
    import UserThumbnail from '@/common/components/UserThumbnail.vue';
    import PasswordController from '@actions/Users/PasswordController';

    const {currentUser, csrfTokenName, csrfTokenValue} = useCraftData();
    const logoutForm = useTemplateRef('logoutForm');

    const menuItems = computed((): ActionItem[] => {
        return [
            {
                type: 'display' as const,
                is: CurrentUser,
            },
            {type: 'hr' as const},
            {
                href: UsersController.edit['/{cpTrigger?}/myaccount']().url,
                label: t('Profile'),
            },
            {
                href: PermissionsController.index[
                    '/{cpTrigger?}/myaccount/permissions'
                ]().url,
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
                type: 'button',
                variant: 'danger',
                label: t('Sign out'),
                onClick: () => logoutForm.value?.requestSubmit(),
            },
        ];
    });
</script>

<template>
    <form :action="logout().url" method="post" ref="logoutForm">
        <input
            v-if="csrfTokenName && csrfTokenValue"
            type="hidden"
            :name="csrfTokenName"
            :value="csrfTokenValue"
        />
    </form>
    <ActionMenu :actions="menuItems" :label="currentUser!.username">
        <template #invoker>
            <craft-button
                slot="invoker"
                type="button"
                aria-label="User menu"
                variant="none"
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
