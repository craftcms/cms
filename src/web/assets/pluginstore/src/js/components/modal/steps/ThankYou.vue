<template>
    <step>
        <template slot="main">
            <div id="thank-you-message">
                <icon icon="check-circle" size="4xl" />
                <h2>{{ "Thank You!"|t('app') }}</h2>
                <p class="light">{{ "Your order has been processed successfully."|t('app') }}</p>
                <div class="mt-6">
                    <btn kind="primary" :href="managePluginsUrl">{{ "Manage plugins"|t('app') }}</btn>
                </div>
            </div>
        </template>
    </step>
</template>

<script>
    /* global Craft */

    import Step from '../Step'

    export default {
        components: {
            Step,
        },

        computed: {
            managePluginsUrl() {
                return Craft.getCpUrl('settings/plugins')
            }
        },
    }
</script>
