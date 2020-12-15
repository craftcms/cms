<template>
    <div class="plugin-editions-edition">
        <div class="description">
            <edition-badge v-if="plugin.editions.length > 1" :name="edition.name" block big></edition-badge>
            <div class="price">
                <template v-if="!isPluginEditionFree(edition)">
                    <template v-if="licensedEdition && licensedEdition.handle !== edition.handle && licensedEdition.price > 0 && licenseValidOrAstray">
                        <del>{{edition.price|currency}}</del>
                        {{(edition.price - licensedEdition.price)|currency}}
                    </template>
                    <template v-else>
                        {{edition.price|currency}}
                    </template>
                </template>
                <template v-else>
                    {{ "Free"|t('app') }}
                </template>
            </div>
            <p v-if="!isPluginEditionFree(edition)" class="-mt-8 py-6 text-grey-dark">
                {{ "Price includes 1 year of updates."|t('app') }}<br />
                {{ "{renewalPrice}/year per site for updates after that."|t('app', {renewalPrice: $options.filters.currency(edition.renewalPrice)}) }}
            </p>

            <ul v-if="plugin.editions.length > 1 && edition.features && edition.features.length > 0">
                <li v-for="(feature, key) in edition.features" :key="key">
                    <icon icon="check" />
                    {{feature.name}}

                    <info-hud v-if="feature.description">
                        {{feature.description}}
                    </info-hud>
                </li>
            </ul>
        </div>

        <plugin-actions :plugin="plugin" :edition="edition"></plugin-actions>
    </div>
</template>

<script>
    import {mapState, mapGetters} from 'vuex'
    import PluginActions from './PluginActions'
    import InfoHud from './InfoHud'
    import EditionBadge from './EditionBadge'
    import licensesMixin from '../mixins/licenses'

    export default {
        mixins: [licensesMixin],

        props: ['plugin', 'edition'],

        components: {
            PluginActions,
            InfoHud,
            EditionBadge,
        },

        computed: {
            ...mapState({
                cart: state => state.cart.cart,
            }),

            ...mapGetters({
                isPluginEditionFree: 'pluginStore/isPluginEditionFree',
                getPluginEdition: 'pluginStore/getPluginEdition',
                getPluginLicenseInfo: 'craft/getPluginLicenseInfo',
            }),


            pluginLicenseInfo() {
                if (!this.plugin) {
                    return null
                }

                return this.getPluginLicenseInfo(this.plugin.handle)
            },

            licensedEdition() {
                if (!this.pluginLicenseInfo) {
                    return null
                }
                
                return this.getPluginEdition(this.plugin, this.pluginLicenseInfo.licensedEdition)
            }
        },
    }
</script>

<style lang="scss">
    .plugin-editions-edition {
        @apply .border .border-grey-light .border-solid .p-8 .rounded .text-center .flex .flex-col;

        .description {
            @apply .flex-1;

            .edition-name {
                @apply .border-b .border-grey-light .border-solid .text-grey-dark .inline-block .py-1 .uppercase .text-lg .font-bold;
            }

            .price {
                @apply .text-3xl .font-bold .my-8;
            }

            ul {
                @apply .text-left .mb-8;

                li {
                    @apply .py-2 .border-b .border-grey-lighter .border-solid;

                    &:first-child {
                        @apply .border-t;
                    }
                }
            }
        }
    }
</style>
