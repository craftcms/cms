<script setup lang="ts">
  import {t, toHandle} from '@craftcms/ui';
  import {router, useForm} from '@inertiajs/vue3';
  import type {ActionItem, UserGroup} from '@/common/types';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftHandleInput from '@craftcms/ui/vue/CraftInputHandle.vue';
  import CraftTextarea from '@craftcms/ui/vue/CraftTextarea.vue';
  import PermissionList from '@/modules/permissions/components/PermissionList.vue';
  import {destroy, store} from '@actions/Settings/Users/UserGroupsController';
  import {computed} from 'vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {useAppLayout} from '@/common/composables/useAppLayout';

  type PermissionGroup = Omit<
    CraftCms.Cms.User.Data.PermissionGroup,
    'permissions'
  > & {
    permissions: Record<string, CraftCms.Cms.User.Data.Permission>;
  };

  const props = defineProps<{
    group: UserGroup;
    brandNew: boolean;
    permissions: Array<PermissionGroup>;
    formActions?: Array<ActionItem>;
    redirect?: string;
    toolbar?: string;
    errors: Record<any, any> | null;
    readOnly?: boolean;
    redirectUrl?: string;
  }>();

  const form = useForm({
    id: props.group.id,
    redirect: props.redirect,
    name: props.group.name,
    handle: props.group.handle,
    description: props.group.description ?? '',
    permissions: props.group.permissions ?? [],
  });
  const initialPermissions = new Set(form.permissions);

  // Auto-generate handle from name for new sections
  const handleGenerator = useInputGenerator(
    () => form.name,
    (v) => (form.handle = toHandle(v))
  );

  // For existing groups, mark handle as already dirty
  if (!props.brandNew) {
    handleGenerator.stop();
  }

  const {save} = useSettingsSave(form, store, {
    passwordConfirmation: {
      required: ({permissions}) =>
        !props.brandNew &&
        (permissions.length !== initialPermissions.size ||
          permissions.some(
            (permission) => !initialPermissions.has(permission)
          )),
    },
  });

  const actions = computed(() => {
    if (props.readOnly || !props.group.id) {
      return [];
    }

    return [
      {
        variant: 'danger',
        label: t('Delete group'),
        onClick: () => {
          if (
            confirm(
              t('Are you sure you want to delete “{name}”?', {
                name: props.group.name,
              })
            )
          ) {
            router.delete(destroy({groupId: props.group.id}));
          }
        },
      },
    ];
  });

  useAppLayout({form, formActions: actions.value, onSave: save});
</script>

<template>
  <craft-pane appearance="raised">
    <div class="grid gap-3">
      <CraftInput
        :label="t('Name')"
        id="name"
        data-error-key="name"
        :autofocus="true"
        :required="true"
        :disabled="readOnly"
        :error="errors?.name"
        name="name"
        v-model="form.name"
      />

      <CraftHandleInput
        :label="t('Handle')"
        id="handle"
        v-model="form.handle"
        :autocorrect="false"
        :autocapitalize="false"
        name="handle"
        :error="errors?.handle"
        :required="true"
        data-error-key="handle"
        :disabled="readOnly"
        @change="handleGenerator.markDirty()"
      />

      <CraftTextarea
        :label="t('Description')"
        id="description"
        name="description"
        v-model="form.description"
        :error="errors?.description"
        data-error-key="description"
        :disabled="readOnly"
      />
    </div>

    <hr class="my-8" />

    <h2 class="text-lg mb-3">{{ t('Permissions') }}</h2>

    <div class="grid gap-3">
      <div v-for="set in permissions" :key="set.handle">
        <PermissionList
          :heading="set.heading"
          :permissions="set.permissions"
          :permission-keys="set.keys"
          v-model="form.permissions"
        />
      </div>
    </div>
  </craft-pane>
</template>
