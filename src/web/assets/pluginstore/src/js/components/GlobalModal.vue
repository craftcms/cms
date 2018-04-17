<template>
    <div class="hidden">
        <div ref="globalmodal" class="globalmodal modal">
            <div class="globalmodalcontent">
                <template v-if="modalStep === 'plugin-details'">
                    <plugin-details :pluginId="pluginId"></plugin-details>
                </template>

                <template v-else-if="modalStep === 'cart'">
                    <header class="header">
                        <h1>Cart</h1>
                    </header>
                    <div class="body">
                        <div class="content">
                            <cart @continue-shopping="$root.closeGlobalModal()"></cart>
                        </div>
                    </div>
                </template>

                <template v-else-if="modalStep === 'identity'">
                    <header class="header">
                        <div class="btn-left"><a @click="back()">← Back</a></div>
                        <h1>Identity</h1>
                    </header>
                    <div class="body">
                        <div class="content">
                            <identity></identity>
                        </div>
                    </div>
                </template>

                <template v-else-if="modalStep === 'payment'">
                    <header class="header">
                        <div class="btn-left"><a @click="back()">← Back</a></div>
                        <h1>Payment</h1>
                    </header>
                    <div class="body">
                        <div class="content">
                            <payment></payment>
                        </div>
                    </div>
                </template>

                <template v-else-if="modalStep === 'thankYou'">
                    <div class="body">
                        <thank-you></thank-you>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapState} from 'vuex'

    export default {

        components: {
            PluginDetails: require('./PluginDetails'),
            Cart: require('./checkout/Cart'),
            Payment: require('./checkout/Payment'),
            ThankYou: require('./checkout/ThankYou'),
            Identity: require('./checkout/Identity'),
        },

        props: ['pluginId', 'show'],

        data() {
            return {
                modal: null,
            }
        },

        computed: {

            ...mapState({
                identityMode: state => state.cart.identityMode,
            }),

            modalStep() {
                return this.$root.modalStep
            }

        },

        watch: {

            show(show) {
                if (show) {
                    this.modal.show()
                } else {
                    this.modal.hide()
                }
            }

        },

        methods: {

            back() {
                if (this.identityMode === 'craftid' || this.modalStep === 'identity') {
                    this.$root.openGlobalModal('cart')
                } else {
                    this.$root.openGlobalModal('identity')
                }
            }

        },

        mounted() {
            let $this = this

            this.modal = new Garnish.Modal(this.$refs.globalmodal, {
                autoShow: false,
                resizable: true,
                onHide() {
                    $this.$emit('update:show', false)
                }
            })
        }

    }
</script>
