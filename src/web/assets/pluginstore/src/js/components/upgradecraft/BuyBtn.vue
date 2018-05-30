<template>
    <div class="btngroup">
        <!-- Show the "Buy" button if this edition is greater than the licensed edition -->
        <template v-if="edition > licensedEdition">
            <template v-if="!isCmsEditionInCart(editionHandle)">
                <div @click="buyCraft(editionHandle)" class="btn submit">{{ "Buy now"|t('app') }}</div>
            </template>
            <template v-else>
                <div class="btn submit disabled">{{ "Added to cart"|t('app') }}</div>
            </template>
        </template>

        <!-- Show the "Try" button if they're on a testable domain, this is not the current edition, and is greater than the licensed edition -->
        <template v-if="canTestEditions && edition != CraftEdition && edition > licensedEdition">
            <div @click="installCraft(editionHandle)" class="btn">{{ "Try for free"|t('app') }}</div>
        </template>

        <!-- Show the "Reactivate" button if they’re licensed to use this edition but not currently on it -->
        <template v-if="edition == licensedEdition && edition != CraftEdition">
            <div @click="installCraft(editionHandle)" class="btn">{{ "Reactivate"|t('app') }}</div>
        </template>

        <div v-if="loading" class="spinner"></div>
    </div>
</template>

<script>
    import {mapState, mapGetters, mapActions} from 'vuex'

    export default {

        props: ['edition', 'edition-handle'],

        data() {
            return {
                loading: false,
            }
        },

        computed: {

            ...mapState({
                cart: state => state.cart.cart,
                licensedEdition: state => state.craft.licensedEdition,
                canTestEditions: state => state.craft.canTestEditions,
                CraftEdition: state => state.craft.CraftEdition,
            }),

            ...mapGetters({
                isCmsEditionInCart: 'isCmsEditionInCart',
            })

        },

        methods: {
            ...mapActions({
                addToCart: 'addToCart',
                tryEdition: 'tryEdition',
                getCraftData: 'getCraftData',
            }),

            buyCraft(edition) {
                this.loading = true

                const item = {
                    type: 'cms-edition',
                    edition: edition,
                    licenseKey: window.cmsLicenseKey,
                    autoRenew: false,
                }

                this.addToCart([item])
                    .then(() => {
                        this.loading = false
                        this.$root.openModal('cart')
                    })
                    .catch(() => {
                        this.loading = false
                    })
            },

            installCraft(edition) {
                this.loading = true

                this.tryEdition(edition)
                    .then(() => {
                        this.getCraftData()
                            .then(() => {
                                this.loading = false
                                this.$root.displayNotice("Craft CMS edition changed.")
                            })
                    })
                    .catch(() => {
                        this.loading = false
                        this.$root.displayError("Couldn’t change Craft CMS edition.")
                    })
            },

        },

    }
</script>

