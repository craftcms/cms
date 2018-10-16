<template>
    <div>
        <template v-if="CraftEdition == edition">
            <template v-if="licensedEdition >= edition">
                <div class="license-status installed" data-icon="check">{{ "Installed"|t('app') }}</div>
            </template>
            <template v-else>
                <div class="license-status installed" data-icon="check">{{ "Installed as a trial"|t('app') }}</div>
            </template>
        </template>

        <template v-else-if="licensedEdition == edition">
            <div class="license-status licensed" data-icon="check">{{ "Licensed"|t('app') }}</div>
        </template>
    </div>
</template>

<script>
    import {mapState} from 'vuex'

    export default {

        props: ['edition'],

        computed: {

            ...mapState({
                cart: state => state.cart.cart,
                licensedEdition: state => state.craft.licensedEdition,
                CraftEdition: state => state.craft.CraftEdition,
            }),

        }

    }
</script>
