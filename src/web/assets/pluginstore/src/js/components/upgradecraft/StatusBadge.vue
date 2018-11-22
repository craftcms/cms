<template>
    <div>
        <template v-if="CraftEdition == edition">
            <template v-if="licensedEdition >= edition">
                <license-status status="installed" :description="$options.filters.t('Installed', 'app')"></license-status>
            </template>
            <template v-else>
                <license-status status="installed" :description="$options.filters.t('Installed as a trial', 'app')"></license-status>
            </template>
        </template>

        <template v-else-if="licensedEdition == edition">
            <license-status status="licensed" :description="$options.filters.t('Licensed', 'app')"></license-status>
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
                cart: state => state.cart.cart,
                licensedEdition: state => state.craft.licensedEdition,
                CraftEdition: state => state.craft.CraftEdition,
            }),

        }

    }
</script>
