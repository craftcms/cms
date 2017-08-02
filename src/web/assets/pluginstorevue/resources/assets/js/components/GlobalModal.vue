<template>
    <div class="hidden">
        <div ref="globalmodal" class="globalmodal modal">

            <template v-if="modalStep === 'plugin-details'">
                <div class="body">
                    <plugin-details :plugin="plugin"></plugin-details>
                </div>
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

        </div>
    </div>
</template>

<script>
    import PluginDetails from './PluginDetails';
    import Cart from './Cart';
    import Payment from './Payment';

    export default {
        name: 'globalModal',
        props: ['plugin', 'show'],
        components: {
            PluginDetails,
            Cart,
            Payment
        },
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


<style>
    .globalmodal > div {
        position: relative;
        height: calc(100% - 72px);
    }

    .globalmodal > div header .btn-left {
        position: absolute;
        top: 28px;
        left: 24px;
    }

    .globalmodal > div header h1 {
        text-align: center;
    }

    .globalmodal .body {
        position: relative;
        height: 100%;
    }
    .globalmodal .content {
        margin: -24px;
        padding: 24px;
        overflow: auto;
        height: 100%;
    }
</style>