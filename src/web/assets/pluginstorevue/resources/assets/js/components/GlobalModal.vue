<template>
    <div class="hidden">
        <div ref="globalmodal" class="globalmodal modal">
            <plugin-details v-if="modalStep === 'plugin-details'" :plugin="plugin"></plugin-details>
            <cart v-else-if="modalStep === 'cart'" @continue-shopping="$root.closeGlobalModal()"></cart>
            <payment v-else-if="modalStep === 'payment'"></payment>
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


<style scoped>
    .globalmodal > div {
        position: relative;
        height: calc(100% - 72px);
    }
    .globalmodal > div header {
        /*display: flex;*/
    }

    .globalmodal > div header .btn-left {
        position: absolute;
        top: 28px;
        left: 24px;
    }

    .globalmodal > div header h1 {
        text-align: center;
    }

    .body {
        position: relative;
        height: 100%;
    }
    .content {
        margin: -24px;
        padding: 24px;
        overflow: auto;
        height: 100%;
    }
</style>