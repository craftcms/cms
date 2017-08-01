<template>
    <div class="hidden">
        <div ref="globalmodal" class="modal">
            <plugin-details v-if="modalStep === 'plugin-details'" :plugin="plugin"></plugin-details>
            <cart v-else-if="modalStep === 'cart'" @continue-shopping="closeGlobalModal()"></cart>
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
