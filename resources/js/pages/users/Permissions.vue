<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import {router, useForm, usePage} from '@inertiajs/vue3';
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

  const page = usePage<UserPermissionsPageProps>();

  const form = useForm({
    admin: page.props.user.admin,
    groups: page.props.currentGroupIds,
    permissions: page.props.directPermissions,
  });

  const routeAction = () =>
    page.props.user.isCurrent
      ? update['/{cpTrigger?}/myaccount/permissions']()
      : update['/{cpTrigger?}/users/{userId}/permissions']({
          userId: page.props.user.id,
        });

  const {save} = useSettingsSave(form, routeAction, {
    elevatedFields: ['admin', 'groups', 'permissions'],
  });

  const lockedPermissions = computed(() => [
    ...new Set(
      form.groups.flatMap(
        (groupId) =>
          page.props.groups.find((group) => group.id === groupId)
            ?.permissions ?? []
      )
    ),
  ]);

  const additionalButtons = computed(() => {
    if (!page.props.can.canSendActivationEmail) {
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

    <craft-field-group
      v-if="page.props.can.assignUserGroups"
      class="grid gap-3"
    >
      <h2 class="text-lg m-0!">{{ t('User Groups') }}</h2>

      <UserGroupSelect
        :groups="page.props.groups"
        :can-create="page.props.can.createGroups"
        :error="form.errors.groups"
        v-model="form.groups"
        @created="router.reload({only: ['groups']})"
      />
    </craft-field-group>

    <hr
      class="my-3"
      v-if="
        page.props.can.assignUserGroups && page.props.can.assignUserPermissions
      "
    />

    <craft-field-group v-if="page.props.can.assignUserPermissions">
      <h2 class="text-lg m-0!">{{ t('Permissions') }}</h2>

      <CraftSwitch
        v-if="page.props.showAdminSwitch"
        :label="t('Admin')"
        id="admin"
        name="admin"
        type="checkbox"
        value="1"
        v-model="form.admin"
        :error="form.errors.admin"
      />

      <craft-callout
        v-if="page.props.teamPermissionsNotice && !form.admin"
        data-color="info"
      >
        <template v-if="page.props.teamPermissionsNotice.allowAdminChanges">
          {{ t('Team permissions can be managed from') }}
          <CpLink :href="page.props.teamPermissionsNotice.settingsUrl">
            {{ t('User Permissions') }}
          </CpLink>
        </template>
        <template v-else>
          {{
            t(
              'Team permissions can be managed from {path} on a development environment.',
              {
                path: page.props.teamPermissionsNotice.path.join(' -> '),
              }
            )
          }}
        </template>
      </craft-callout>

      <craft-field-group v-if="!form.admin">
        <PermissionTree
          :groups="page.props.permissions"
          :locked-permissions="lockedPermissions"
          :disabled="Boolean(page.props.teamPermissionsNotice)"
          v-model="form.permissions"
        />
      </craft-field-group>
    </craft-field-group>
  </UserScreen>
</template>
