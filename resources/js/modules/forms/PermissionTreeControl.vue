<script setup lang="ts">
    import PermissionTree from '@craftcms/ui/vue/CraftPermissionTree.vue';
    import type {PermissionTreeGroup} from '@craftcms/ui/components/permission-tree/permission-tree';
    import {computed} from 'vue';
    import {inputName} from './runtime';
    import type {FormControlPayload} from './types';

    type PermissionTreeProps = {
        ariaLabel: string | null;
        groups: PermissionTreeGroup[];
        lockedPermissions: string[];
    };

    const props = defineProps<{
        control: FormControlPayload<PermissionTreeProps>;
        value: unknown;
        label?: string;
        editable: boolean;
        invalid: boolean;
        required: boolean;
    }>();
    const emit = defineEmits<{
        (event: 'update:value', value: string[]): void;
    }>();
    const selected = computed(() =>
        Array.isArray(props.value) ? props.value.map(String) : []
    );
</script>

<template>
    <PermissionTree
        role="group"
        :aria-label="control.props.ariaLabel ?? label"
        :aria-invalid="invalid ? 'true' : undefined"
        :aria-required="required ? 'true' : undefined"
        :groups="control.props.groups"
        :locked-permissions="control.props.lockedPermissions"
        :name="editable ? inputName(control.path) : ''"
        :disabled="!editable"
        :model-value="selected"
        @update:model-value="emit('update:value', $event ?? [])"
    />
</template>
