<template>
    <div class="cms-edition-status-badge">
        <template v-if="CraftEdition == edition">
            <template v-if="licensedEdition >= edition">
                <license-status status="installed" :description="'Installed'|t('app')"></license-status>
            </template>
            <template v-else>
                <license-status status="installed" :description="'Installed as a trial'|t('app')"></license-status>
            </template>
        </template>

        <template v-else-if="licensedEdition == edition">
            <license-status status="licensed" :description="'Licensed'|t('app')"></license-status>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import LicenseStatus from '../LicenseStatus'

    export default {
        props: ['edition'],

        components: {
            LicenseStatus
        },

        computed: {
            ...mapState({
                CraftEdition: state => state.craft.CraftEdition,
                licensedEdition: state => state.craft.licensedEdition,
            }),
        }
    }
</script>
