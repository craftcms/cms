<template>
    <div class="buttons flex-no-shrink">
        <template v-if="plugin.editions[0].price != null && plugin.editions[0].price !== '0.00'">
            <template v-if="isInstalled(plugin)">
                <template v-if="pluginHasLicenseKey(plugin.handle)">
                    <license-status status="installed" :description="$options.filters.t('Installed', 'app')"></license-status>
                </template>
                <template v-else>
                    <license-status status="installed" :description="$options.filters.t('Installed as a trial', 'app')"></license-status>

                    <a v-if="isInCart(plugin)" class="btn submit disabled">{{ "Added to cart"|t('app') }}</a>
                    <a v-else @click="chooseEdition(plugin)" class="btn submit" :title="buyBtnTitle">{{ plugin.editions[0].price|currency }}</a>
                </template>
            </template>

            <template v-else>
                <template v-if="allowUpdates">
                    <form method="post">
                        <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue">
                        <input type="hidden" name="action" value="pluginstore/install">
                        <input type="hidden" name="packageName" :value="plugin.packageName">
                        <input type="hidden" name="handle" :value="plugin.handle">
                        <input type="hidden" name="version" :value="plugin.version">
                        <input type="submit" class="btn" :value="'Try'|t('app')">
                    </form>

                    <a v-if="isInCart(plugin)" class="btn submit disabled">{{ "Added to cart"|t('app') }}</a>
                    <a v-else @click="chooseEdition(plugin)" class="btn submit" :title="buyBtnTitle">{{ plugin.editions[0].price|currency }}</a>
                </template>
            </template>
        </template>
        <div v-else>
            <a v-if="isInstalled(plugin)" class="btn submit disabled">{{ "Installed"|t('app') }}</a>

            <div v-else-if="allowUpdates">
                <form method="post">
                    <input type="hidden" :name="csrfTokenName" :value="csrfTokenValue">
                    <input type="hidden" name="action" value="pluginstore/install">
                    <input type="hidden" name="packageName" :value="plugin.packageName">
                    <input type="hidden" name="handle" :value="plugin.handle">
                    <input type="hidden" name="version" :value="plugin.version">
                    <input type="submit" class="btn submit" :value="'Install'|t('app')">
                </form>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapState, mapGetters, mapActions} from 'vuex'

    export default {

        props: ['plugin'],


        computed: {

            ...mapGetters({
                isInstalled: 'pluginStore/isInstalled',
                isInCart: 'cart/isInCart',
            }),

            buyBtnTitle() {
                let price = 0

                if (this.plugin) {
                    price = this.plugin.editions[0].price
                }

                return this.$root.$options.filters.t('Buy now for {price}', 'app', {
                    price: this.$root.$options.filters.currency(price)
                })
            },

            allowUpdates() {
                return window.allowUpdates
            },

            csrfTokenName() {
                return Craft.csrfTokenName
            },

            csrfTokenValue() {
                return Craft.csrfTokenValue
            },

        }

    }
</script>