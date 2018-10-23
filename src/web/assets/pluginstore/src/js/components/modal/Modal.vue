<template>
    <div class="hidden">
        <div ref="pluginstoremodal" id="pluginstore-modal" class="pluginstore-modal modal" :class="'step-'+modalStep">
            <plugin-details v-if="modalStep === 'plugin-details'" :pluginId="pluginId"></plugin-details>
            <cart v-else-if="modalStep === 'cart'" @continue-shopping="$root.closeModal()"></cart>
            <identity v-else-if="modalStep === 'identity'" @back="back()"></identity>
            <payment v-else-if="modalStep === 'payment'" @back="back()"></payment>
            <thank-you v-else-if="modalStep === 'thank-you'"></thank-you>
        </div>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import Payment from './steps/Payment'
    import PluginDetails from './steps/PluginDetails'
    import Cart from './steps/Cart'
    import Identity from './steps/Identity'
    import ThankYou from './steps/ThankYou'

    export default {

        components: {
            PluginDetails,
            Cart,
            Identity,
            Payment,
            ThankYou,
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
                    this.$root.openModal('cart')
                } else {
                    this.$root.openModal('identity')
                }
            }

        },

        mounted() {
            let $this = this

            this.modal = new Garnish.Modal(this.$refs.pluginstoremodal, {
                autoShow: false,
                resizable: true,
                onHide() {
                    $this.$emit('update:show', false)
                }
            })
        }

    }
</script>
