<template>
    <div class="plugin-editions-edition">
        <div class="description">
            <h4 class="edition-name">{{edition.name}}</h4>
            <div class="price">
                <template v-if="!isPluginEditionFree(edition)">
                    {{edition.price|currency}}
                </template>
                <template v-else>
                    {{ "Free"|t('app') }}
                </template>
            </div>

            <p v-if="!isPluginEditionFree(edition)" class="py-6 text-grey-dark">
                Price includes 1 year of updates.<br />
                {{ edition.renewalPrice|currency }}/year per site for updates after that.
            </p>

            <ul v-if="edition.features.length > 0">
                <li v-for="feature in edition.features">
                    <font-awesome-icon icon="check"></font-awesome-icon>
                    {{feature.name}}
                    <font-awesome-icon icon="info-circle" />
                    <!--
                    <template v-if="feature.description">
                        — {{feature.description}}
                    </template>
                    -->
                </li>
            </ul>
        </div>

        <div v-if="cart" class="actions">
            <div class="buttons">
                <template v-if="isInCart(plugin)">
                    <btn type="primary" @click="$root.openModal('cart')" block large>{{ "Already in your cart"|t('app') }}</btn>
                </template>
                <template v-else>
                    <btn type="primary" :disabled="isPluginEditionFree(edition) || isInCart(plugin)" @click="addEditionToCart(edition.handle)" block large>{{ "Add to cart"|t('app') }}</btn>
                </template>
                <div class="spinner" v-if="loading"></div>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapState, mapGetters} from 'vuex'

    export default {

        props: ['plugin', 'edition'],

        data() {
            return {
                loading: false,
            }
        },

        computed: {

            ...mapState({
                cart: state => state.cart.cart,
            }),

            ...mapGetters({
                isPluginEditionFree: 'pluginStore/isPluginEditionFree',
                isInCart: 'cart/isInCart',
            }),

        },

        methods: {

            addEditionToCart(editionHandle) {
                this.loading = true

                const item = {
                    type: 'plugin-edition',
                    plugin: this.plugin.handle,
                    edition: editionHandle,
                    autoRenew: false,
                    cmsLicenseKey: window.cmsLicenseKey,
                }

                this.$store.dispatch('cart/addToCart', [item])
                    .then(() => {
                        this.loading = false
                        this.$root.openModal('cart')
                    })
            },

        }

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
                @apply .text-3xl .font-bold .mt-8;
            }

            ul {
                @apply .text-left .mb-8;

                li {
                    @apply .py-2 .border-b .border-grey-lighter .border-solid;

                    &:first-child {
                        @apply .border-t;
                    }

                    svg[data-icon="info-circle"] {
                        path {
                            fill: #ccc;
                        }
                    }
                }
            }
        }

        .actions {
            .buttons {
                position: relative;
                .spinner {
                    position: absolute;
                    top: 6px;
                    right: -28px;
                }
            }
        }
    }
</style>