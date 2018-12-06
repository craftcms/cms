<template>
    <div class="plugin-editions-edition">
        <div class="description">
            <h4 class="edition-name">{{edition.name}}</h4>
            <div class="price">
                {{edition.price|currency}}
            </div>
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

        <div class="action">
            <div class="buttons">
                <btn type="primary" @click="addEditionToCart(edition.handle)" block large>{{ "Add to cart"|t('app') }}</btn>
                <div class="spinner" v-if="loading"></div>
            </div>

            <p class="mt-4 text-grey-dark mb-0">
                Price includes 1 year of updates.<br />
                {{ edition.renewalPrice|currency }}/year per site for updates after that.
            </p>
        </div>
    </div>
</template>

<script>
    export default {

        props: ['plugin', 'edition'],

        data() {
            return {
                loading: false,
            }
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
                @apply .text-3xl .font-bold .py-8;
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