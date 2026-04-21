<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import {router, useForm} from '@inertiajs/vue3';
  import type {ActionItem, UserGroup} from '@/types';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftHandleInput from '@craftcms/cp/vue/CraftInputHandle.vue';
  import CraftTextarea from '@craftcms/cp/vue/CraftTextarea.vue';
  import Pane from '@/components/Pane.vue';
  import PermissionList from '@/components/PermissionList.vue';
  import {hasNested, type PermissionItem} from '@/utils/permissions';
  import {destroy, store} from '@actions/Settings/UserGroupsController';
  import {computed} from 'vue';
  import {useSettingsSave} from '@/composables/useSettingsSave';

  const props = defineProps<{
    group: UserGroup;
    permissions: Array<{
      heading: string;
      handle: string;
      permissions: Record<string, PermissionItem>;
    }>;
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

  function getAllKeys(
    permissions: Record<string, PermissionItem>
  ): Array<string> {
    return Object.values(permissions).flatMap((item) => [
      item.key,
      ...(hasNested(item) ? getAllKeys(item.nested) : []),
    ]);
  }

  const permissionSets = computed(() => {
    return props.permissions.reduce<Record<string, Array<string>>>(
      (acc, set) => {
        acc[set.handle] = getAllKeys(set.permissions).map((v) =>
          v.toLowerCase()
        );
        return acc;
      },
      {}
    );
  });

  function allSelected(set?: Array<string>) {
    if (!set) {
      return false;
    }

    const selected = new Set(form.permissions);
    return set.every((key) => selected.has(key));
  }

  function toggleSet(handle: string) {
    const setKeys = permissionSets.value[handle];
    if (!setKeys) {
      return;
    }

    if (allSelected(setKeys)) {
      const keysToRemove = new Set(setKeys);
      form.permissions = form.permissions.filter(
        (key) => !keysToRemove.has(key)
      );
    } else {
      form.permissions = [...new Set([...form.permissions, ...setKeys])];
    }
  }

  const {save} = useSettingsSave(form, store);

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
</script>

<template>
  <AppLayout :form="form" :form-actions="actions" @save="save">
    <Pane appearance="raised">
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
          <div class="flex gap-2 items-center">
            <h3 class="mb-1 text-base" :id="`content-heading-${set.handle}`">
              {{ set.heading }}
            </h3>

            <craft-button
              type="button"
              size="small"
              appearance="plain"
              @click="toggleSet(set.handle)"
            >
              <template v-if="allSelected(permissionSets[set.handle])">
                {{ t('Deselect all') }}
              </template>
              <template v-else>
                {{ t('Select all') }}
              </template>
            </craft-button>
          </div>

          <PermissionList
            :permissions="set.permissions"
            v-model="form.permissions"
          />
        </div>
      </div>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
