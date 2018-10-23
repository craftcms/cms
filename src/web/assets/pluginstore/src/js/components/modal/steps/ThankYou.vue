<template>
    <step>
        <template slot="main">
            <div id="thank-you-message">
                <div id="graphic" class="spinner big success"></div>
                <h2>{{ "Thank You!"|t('app') }}</h2>
                <p class="light">{{ "Your order has been processed successfully."|t('app') }}</p>
                <p>
                    <a :href="managePluginsUrl" class="btn submit">{{ "Manage plugins"|t('app') }}</a>
                </p>
            </div>
        </template>
    </step>
</template>

<script>
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
