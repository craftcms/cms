<script setup lang="ts">
  import {computed, ref} from 'vue';
  import {router} from '@inertiajs/vue3';
  import {ButtonVariant, t} from '@craftcms/ui';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import SlideoutButton from '@/common/components/SlideoutButton.vue';
  import Text from '@/common/components/Text.vue';
  import Tooltip from '@/common/components/Tooltip.vue';
  import {create} from '@actions/Settings/Users/UserGroupsController';

  type UserGroupOption =
    CraftCms.Cms.Http.ViewModels.UserPermissionsViewModel['groups'][number];

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Array<number>): void;
  }>();

  const props = withDefaults(
    defineProps<{
      modelValue: Array<number>;
      groups: Array<UserGroupOption>;
      canCreate?: boolean;
      error?: string;
    }>(),
    {
      canCreate: false,
    }
  );

  const groupQuery = ref('');

  function groupLabel(group: UserGroupOption) {
    return t(group.name, undefined, 'site');
  }

  const selectedGroups = computed(() => {
    return props.modelValue
      .map((groupId) => props.groups.find((group) => group.id === groupId))
      .filter((group): group is UserGroupOption => Boolean(group));
  });

  const selectableGroups = computed(() => {
    const query = groupQuery.value.toLowerCase();

    if (!query) {
      return props.groups;
    }

    return props.groups.filter((group) => {
      return [groupLabel(group), group.handle, group.description ?? ''].some(
        (value) => value.toLowerCase().includes(query)
      );
    });
  });

  function isSelected(groupId: number) {
    return props.modelValue.includes(groupId);
  }

  function selectGroup(group: UserGroupOption) {
    if (isSelected(group.id)) {
      removeGroup(group.id);
      return;
    }

    emit('update:modelValue', [...props.modelValue, group.id]);
  }

  function removeGroup(groupId: number) {
    emit(
      'update:modelValue',
      props.modelValue.filter((selectedGroupId) => selectedGroupId !== groupId)
    );
  }
</script>

<template>
  <div class="grid gap-2">
    <div class="user-group-list">
      <craft-chip v-for="group in selectedGroups" :key="group.id">
        <div class="grid gap-1">
          <div class="flex gap-1">
            <div class="font-bold">{{ groupLabel(group) }}</div>
            <Tooltip v-if="group.description">{{ group.description }}</Tooltip>
          </div>
        </div>

        <craft-button
          slot="suffix"
          icon="x"
          type="button"
          size="small"
          @click="removeGroup(group.id)"
          aria-label="t('Remove {name}', {name: groupLabel(group)})"
          variant="danger-plain"
        >
        </craft-button>
      </craft-chip>
    </div>

    <div class="flex gap-2 items-center">
      <craft-action-menu v-if="groups.length">
        <craft-button
          type="button"
          slot="invoker"
          :variant="ButtonVariant.Dashed"
          icon="chevron-down"
        >
          {{ t('Choose') }}
        </craft-button>

        <div slot="content">
          <div class="p-2">
            <CraftInput :label="t('Search')" v-model="groupQuery" label-sr-only>
              <craft-icon name="search" slot="prefix"></craft-icon>
            </CraftInput>
          </div>
          <hr class="m-0" />
          <div v-if="selectableGroups.length < 1" class="p-2">
            <Text
              template="No user groups match “{query}”"
              :params="{query: groupQuery}"
            />
          </div>
          <template v-else>
            <craft-action-item
              v-for="group in selectableGroups"
              :key="group.id"
              type="checkbox"
              :checked="isSelected(group.id)"
              @click="selectGroup(group)"
            >
              <div>
                {{ groupLabel(group) }}
                <pre>{{ group.handle }}</pre>
                <small v-if="group.description">{{ group.description }}</small>
              </div>
            </craft-action-item>
          </template>
        </div>
      </craft-action-menu>

      <SlideoutButton
        v-if="canCreate"
        :url="create().url"
        @success="router.reload({only: ['groups']})"
      >
        <craft-icon name="plus" slot="prefix"></craft-icon>
        {{ t('Create') }}
      </SlideoutButton>
    </div>

    <ul class="error-list" v-if="error">
      <li>{{ error }}</li>
    </ul>
  </div>
</template>

<style scoped lang="scss">
  .user-group-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: var(--c-spacing-sm);
  }
</style>
