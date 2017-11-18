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
                        <cart @continue-shopping="$root.closeGlobalModal()"></cart>
                    </div>
                </template>

                <template v-else-if="modalStep === 'payment'">
                    <header class="header">
                        <div class="btn-left"><a @click="backToCart()">&lt; Cart</a></div>
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
    import PluginDetails from './PluginDetails';
    import Cart from './Cart';
    import Payment from './Payment';
    import ThankYou from './ThankYou';

    export default {

        components: {
            PluginDetails,
            Cart,
            Payment,
            ThankYou
        },

        props: ['pluginId', 'show'],

        data() {
            return {
                modal: null,
            };
        },

        computed: {

            modalStep() {
                return this.$root.modalStep;
            }

        },

        watch: {

            show(show) {
                if(show) {
                    this.modal.show();
                } else {
                    this.modal.hide();
                }
            }

        },

        methods: {

            backToCart() {
                this.$root.openGlobalModal('cart');
            }

        },

        mounted() {
            let $this = this;

            this.modal = new Garnish.Modal(this.$refs.globalmodal, {
                autoShow: false,
                resizable: true,
                onHide() {
                    $this.$emit('update:show', false);
                }
            });
        }

    }
</script>
