<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import {Form, useForm} from '@inertiajs/vue3';
  import type {UserGroup} from '@/types';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftHandleInput from '@craftcms/cp/vue/CraftInputHandle.vue';
  import CraftTextarea from '@craftcms/cp/vue/CraftTextarea.vue';
  import Pane from '@/components/Pane.vue';
  import PermissionList from '@/components/PermissionList.vue';
  import {hasNested, type PermissionItem} from '@/utils/permissions';
  import {create, store} from '@actions/Settings/UserGroupsController';
  import {computed, ref} from 'vue';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {useEventListener} from '@vueuse/core';

  const props = defineProps<{
    group: UserGroup;
    permissions: Array<{
      heading: string;
      handle: string;
      permissions: Record<string, PermissionItem>;
    }>;
    flash?: Record<any, any>;
    errors: Record<any, any> | null;
    readOnly?: boolean;
  }>();

  const form = useForm({
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

  // Handle cmd + s events
  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save();
    }
  });

  function save() {
    const action = props.group.id ? store(props.group.id) : create();
    form.clearErrors().submit(action);
  }

  const modalActive = ref(false);
</script>

<template>
  <form @submit.prevent="save" method="post">
    <AppLayout>
      <template #actions>
        <TransitionFade>
          <template v-if="form.recentlySuccessful && flash?.success">
            <div class="flex gap-1 items-center text-sm">
              <craft-icon
                name="circle-check"
                style="color: var(--c-color-success-fill-loud)"
              ></craft-icon>
              {{ flash.success }}
            </div>
          </template>
          <template v-if="form.hasErrors">
            <div class="tw:flex tw:gap-1 tw:items-center tw:text-sm">
              <craft-icon
                name="triangle-exclamation"
                style="color: var(--c-color-danger-fill-loud)"
              ></craft-icon>
              {{ t('Could not save settings') }}
            </div>
          </template>
        </TransitionFade>

        <craft-button-group v-if="!readOnly">
          <craft-button
            type="submit"
            variant="primary"
            :loading="form.processing"
          >
            {{ t('Save') }}
          </craft-button>
          <craft-action-menu>
            <craft-button slot="invoker" variant="primary" type="button" icon>
              <craft-icon name="chevron-down"></craft-icon>
            </craft-button>

            <div slot="content">
              <craft-action-item @click="save">
                {{ t('Save and continue editing') }}
                <craft-shortcut slot="suffix" class="ml-2">S</craft-shortcut>
              </craft-action-item>

              <template v-if="group.id">
                <hr />
                <craft-action-item @click="modalActive = true" variant="danger">
                  {{ t('Delete group') }}
                </craft-action-item>
              </template>
            </div>
          </craft-action-menu>
        </craft-button-group>
      </template>

      <Pane appearance="raised">
        <div class="grid gap-3">
          <!-- Error summary -->
          <template v-if="form.hasErrors">
            <craft-callout variant="danger" icon="triangle-exclamation">
              <div slot="title" class="font-bold">
                {{ t('Could not save settings') }}
              </div>
              <ul>
                <li v-for="(error, key) in form.errors" :key="key">
                  {{ error }}
                </li>
              </ul>
            </craft-callout>
          </template>
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
  </form>
</template>

<style scoped lang="scss"></style>
