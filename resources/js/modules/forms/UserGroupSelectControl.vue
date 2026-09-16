<script setup lang="ts">
  import {computed, shallowRef} from 'vue';
  import UserGroupSelect from '@/modules/user/components/UserGroupSelect.vue';
  import {inputName} from './runtime';
  import type {FormControlPayload} from './types';

  type UserGroup = {
    id: number;
    uid: string;
    name: string;
    handle: string;
    description: string | null;
  };

  type UserGroupSelectProps = {
    canCreate: boolean;
    groups: UserGroup[];
  };

  type CreatedUserGroup = Omit<UserGroup, 'uid'> & {uid?: string};

  const props = defineProps<{
    control: FormControlPayload<UserGroupSelectProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string[], kind: 'discrete'): void;
  }>();

  const createdGroups = shallowRef<UserGroup[]>([]);
  const groups = computed(() => [
    ...props.control.props.groups,
    ...createdGroups.value,
  ]);

  const selectedUids = computed(() =>
    Array.isArray(props.value) ? props.value.map(String) : []
  );
  const selectedIds = computed(() =>
    selectedUids.value.flatMap((uid) => {
      const group = groups.value.find((group) => group.uid === uid);

      return group ? [group.id] : [];
    })
  );
  const name = computed(() => inputName(props.control.path));

  function updateValue(groupIds: number[]): void {
    emit(
      'update:value',
      groupIds.flatMap((groupId) => {
        const group = groups.value.find((group) => group.id === groupId);

        return group ? [group.uid] : [];
      }),
      'discrete'
    );
  }

  function addCreatedGroup(group: CreatedUserGroup): void {
    if (!group.uid) {
      throw new Error('The created user group did not include its UID.');
    }

    createdGroups.value = [...createdGroups.value, {...group, uid: group.uid}];
  }
</script>

<template>
  <div
    role="group"
    :aria-label="label"
    :aria-invalid="invalid ? 'true' : undefined"
  >
    <UserGroupSelect
      :model-value="selectedIds"
      :groups="groups"
      :can-create="control.props.canCreate"
      :editable="editable"
      @created="addCreatedGroup"
      @update:model-value="updateValue"
    />

    <template v-if="editable">
      <input type="hidden" :name="name" value="" />
      <input
        v-for="uid in selectedUids"
        :key="uid"
        type="hidden"
        :name="`${name}[]`"
        :value="uid"
      />
    </template>
  </div>
</template>
