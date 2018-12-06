<template>
    <div class="plugin-editions-edition">
        <div class="description">
            <h4 class="edition-name">{{edition.name}}</h4>
            <ul>
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
                <btn type="primary" @click="addEditionToCart(edition.handle)" block large>
                    {{edition.price|currency}}
                </btn>
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
        @apply .border .border-grey-light .border-solid .p-8 .rounded .flex .flex-col .text-center;

        .description {
            @apply .flex-1;
        }

        .action {
            @apply .pt-8;
        }

        .edition-name {
            @apply .text-black .inline-block .py-1 .uppercase .mb-8 .text-lg .font-bold;
        }

        .buttons {
            position: relative;
            .spinner {
                position: absolute;
                top: 6px;
                right: -28px;
            }
        }

        ul {
            @apply .text-left;

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
</style>