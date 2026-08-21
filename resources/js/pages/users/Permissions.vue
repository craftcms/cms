<script setup lang="ts">
    import {computed} from 'vue';
    import {t} from '@craftcms/ui';
    import {useForm, usePage} from '@inertiajs/vue3';
    import CpLink from '@/common/components/CpLink.vue';
    import {useAppLayout} from '@/common/composables/useAppLayout';
    import PermissionTree from '@craftcms/ui/vue/CraftPermissionTree.vue';
    import UserGroupSelect from '@/modules/user/components/UserGroupSelect.vue';
    import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
    import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
    import {update} from '@actions/Users/PermissionsController';
    import UserScreen from '@/modules/user/components/UserScreen.vue';

    defineOptions({
        inheritAttrs: false,
    });

    type UserPermissionsPageProps =
        CraftCms.Cms.Http.ViewModels.UserPermissionsViewModel;

    const props = usePage<UserPermissionsPageProps>().props;

    const form = useForm({
        admin: props.user.admin,
        groups: props.currentGroupIds,
        permissions: props.directPermissions,
    });

    const routeAction = () =>
        props.user.isCurrent
            ? update['/{cpTrigger?}/myaccount/permissions']()
            : update['/{cpTrigger?}/users/{userId}/permissions']({
                  userId: props.user.id,
              });

    const {save} = useSettingsSave(form, routeAction, {
        elevatedFields: ['admin', 'groups', 'permissions'],
    });

    const lockedPermissions = computed(() => [
        ...new Set(
            form.groups.flatMap(
                (groupId) =>
                    props.groups.find((group) => group.id === groupId)
                        ?.permissions ?? []
            )
        ),
    ]);

    const additionalButtons = computed(() => {
        if (!props.can.canSendActivationEmail) {
            return [];
        }

        return [
            {
                label: t('Save and send activation email'),
                onClick: () => save({data: {sendActivationEmail: true}}),
            },
        ];
    });

    useAppLayout(() => ({
        form,
        formAdditionalButtons: additionalButtons.value,
        onSave: save,
    }));
</script>

<template>
    <UserScreen>
        <input
            type="hidden"
            data-user-groups-input
            :value="form.groups.join(',')"
        />
        <input
            type="hidden"
            data-user-permissions-input
            :value="form.permissions.join(',')"
        />

        <craft-field-group v-if="props.can.assignUserGroups" class="grid gap-3">
            <h2 class="text-lg m-0!">{{ t('User Groups') }}</h2>

            <UserGroupSelect
                :groups="props.groups"
                :can-create="props.can.createGroups"
                :error="form.errors.groups"
                v-model="form.groups"
            />
        </craft-field-group>

        <hr
            class="my-3"
            v-if="props.can.assignUserGroups && props.can.assignUserPermissions"
        />

        <craft-field-group v-if="props.can.assignUserPermissions">
            <h2 class="text-lg m-0!">{{ t('Permissions') }}</h2>

            <CraftSwitch
                v-if="props.showAdminSwitch"
                :label="t('Admin')"
                id="admin"
                name="admin"
                type="checkbox"
                value="1"
                v-model="form.admin"
                :error="form.errors.admin"
            />

            <craft-callout
                v-if="props.teamPermissionsNotice && !form.admin"
                data-color="info"
            >
                <template v-if="props.teamPermissionsNotice.allowAdminChanges">
                    {{ t('Team permissions can be managed from') }}
                    <CpLink
                        :href="props.teamPermissionsNotice.settingsUrl"
                        :inertia="false"
                    >
                        {{ t('User Permissions') }}
                    </CpLink>
                </template>
                <template v-else>
                    {{
                        t(
                            'Team permissions can be managed from {path} on a development environment.',
                            {
                                path: props.teamPermissionsNotice.path.join(
                                    ' -> '
                                ),
                            }
                        )
                    }}
                </template>
            </craft-callout>

            <craft-field-group v-if="!form.admin">
                <PermissionTree
                    :groups="props.permissions"
                    :locked-permissions="lockedPermissions"
                    :disabled="Boolean(props.teamPermissionsNotice)"
                    v-model="form.permissions"
                />
            </craft-field-group>
        </craft-field-group>
    </UserScreen>
</template>
