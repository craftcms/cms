<script setup lang="ts">
    import type {Site} from '@/common/types';
    import {t} from '@craftcms/ui';
    import {ref} from 'vue';
    import DeleteSiteModal from '@/modules/sites/components/DeleteSiteModal.vue';

    withDefaults(
        defineProps<{
            site: Site;
        }>(),
        {}
    );

    const modalActive = ref(false);
</script>

<template>
    <div>
        <craft-button
            size="small"
            icon
            type="button"
            variant="danger-plain"
            :disabled="site.primary"
            @click="modalActive = true"
        >
            <craft-icon name="x" :label="t('Delete site')"></craft-icon>
        </craft-button>

        <DeleteSiteModal
            :site="site"
            :open="modalActive"
            @close="modalActive = false"
        />
    </div>
</template>

<style scoped lang="scss"></style>
