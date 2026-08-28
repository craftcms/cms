<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
  import PermissionTree from '@craftcms/ui/vue/CraftPermissionTree.vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import {store, update} from '@actions/Gql/SchemasController';
  import {useForm} from '@inertiajs/vue3';

  type TokenData = Pick<
    CraftCms.Cms.Gql.Data.GqlToken,
    'id' | 'enabled' | 'expiryDate'
  >;

  interface SchemaForm {
    name: string;
    permissions: Array<string>;
    enabled: boolean;
    expiryDate: string;
  }

  type PermissionGroup = Omit<
    CraftCms.Cms.User.Data.PermissionGroup,
    'permissions'
  > & {
    permissions: Record<string, CraftCms.Cms.User.Data.Permission>;
  };

  const props = defineProps<{
    schema: CraftCms.Cms.Gql.Data.GqlSchema;
    token: TokenData | null;
    permissions: Array<PermissionGroup>;
    readOnly?: boolean;
  }>();

  const form = useForm<SchemaForm>({
    name: props.schema.name ?? '',
    permissions: props.schema.scope ?? [],
    enabled: props.token?.enabled ?? true,
    expiryDate: props.token?.expiryDate ?? '',
  });
  const initialPermissions = new Set(form.permissions);

  const routeAction = () => {
    if (!props.schema.id) {
      return store();
    }

    return update({
      schemaId: props.schema.isPublic ? 'public' : props.schema.id,
    });
  };

  const {save} = useSettingsSave(form, routeAction, {
    passwordConfirmation: {
      required: ({permissions}) =>
        permissions.length !== initialPermissions.size ||
        permissions.some((permission) => !initialPermissions.has(permission)),
    },
  });

  useAppLayout({form, onSave: save});
</script>

<template>
  <craft-pane appearance="raised">
    <div class="cp:grid cp:gap-3">
      <CraftInput
        v-if="!schema.isPublic"
        :label="t('Name')"
        :help-text="t('What this schema will be called in the control panel.')"
        id="name"
        name="name"
        v-model="form.name"
        :error="form.errors.name"
        :disabled="readOnly"
        required
        autofocus
      />

      <hr v-if="!schema.isPublic" />

      <section class="cp:grid cp:gap-3">
        <h2 class="cp:text-base">
          {{ t('Choose the available content for querying with this schema:') }}
        </h2>

        <PermissionTree
          :groups="permissions"
          v-model="form.permissions"
          :disabled="readOnly"
        />
      </section>
    </div>
  </craft-pane>

  <LayoutSlot v-if="schema.isPublic" name="details">
    <CraftSwitch
      :label="t('Enabled')"
      id="enabled"
      name="enabled"
      v-model="form.enabled"
      :disabled="readOnly"
      :error="form.errors.enabled"
    />

    <CraftInput
      :label="t('Expiry Date')"
      id="expiryDate"
      name="expiryDate"
      type="datetime-local"
      v-model="form.expiryDate"
      :disabled="readOnly"
      :error="form.errors.expiryDate"
    />
  </LayoutSlot>
</template>
