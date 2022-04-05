<template>
    <div class="ps-container">
        <h1>{{ "Upgrade Craft CMS"|t('app') }}</h1>
        <hr>

        <template v-if="!loading">
            <template v-if="errorMsg">
                <div v-if="errorMsg" class="error">
                    {{ errorMsg }}
                </div>
            </template>
            <template v-else>
                <cms-editions></cms-editions>
            </template>
        </template>
        <template v-else>
            <spinner></spinner>
        </template>
    </div>
</template>

<script>
    import CmsEditions from '../components/upgradecraft/CmsEditions'

    export default {
        components: {
            CmsEditions
        },

        data() {
            return {
                errorMsg: null,
                loading: true,
            }
        },

        mounted() {
            this.$store.dispatch('pluginStore/getCmsEditions')
                .then(() => {
                    this.loading = false
                })
                .catch(() => {
                    this.loading = false
                    this.errorMsg = this.$options.filters.t("Couldn’t load CMS editions.", 'app')
                })
        }
    }
</script>
