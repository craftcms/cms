<template>
    <step>
        <template slot="main">
            <div id="thank-you-message">
                <icon icon="check-circle" size="4xl" />
                <h2>{{ "Thank You!"|t('app') }}</h2>
                <p class="light">{{ "Your order has been processed successfully."|t('app') }}</p>
                <p>
                    <btn kind="primary" :href="managePluginsUrl">{{ "Manage plugins"|t('app') }}</btn>
                </p>
            </div>
        </template>
    </step>
</template>

<script>
    /* global Craft */

    import {mapState} from 'vuex'
    import Step from '../Step'

    export default {
        components: {
            Step,
        },

        computed: {
            ...mapState({
                craftLogo: state => state.craft.craftLogo,
            }),

            managePluginsUrl() {
                return Craft.getCpUrl('settings/plugins')
            }
        },
    }
</script>
