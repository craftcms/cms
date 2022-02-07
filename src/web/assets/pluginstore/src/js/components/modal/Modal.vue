<template>
    <div class="tw-hidden">
        <div ref="pluginstoremodal" id="pluginstore-modal" class="pluginstore-modal modal" :class="'step-'+modalStep">
            <cart v-if="modalStep === 'cart'" @continue-shopping="$root.closeModal()"></cart>
            <identity v-else-if="modalStep === 'identity'" @back="back()"></identity>
            <payment v-else-if="modalStep === 'payment'" @back="back()"></payment>
            <thank-you v-else-if="modalStep === 'thank-you'"></thank-you>
        </div>
    </div>
</template>

<script>
    /* global Garnish */

    import {mapState} from 'vuex'
    import Cart from './steps/Cart'
    import Identity from './steps/Identity'
    import Payment from './steps/Payment'
    import ThankYou from './steps/ThankYou'

    export default {
        components: {
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

<style lang="scss">
    @import "../../../../../../../../packages/craftcms-sass/mixins";

    #pluginstore-modal {
        @apply tw-absolute tw-top-0 tw-left-0;
        max-width: 850px;
        max-height: 650px;
        z-index: 20000;

        .pluginstore-modal-flex {
            @apply tw-absolute tw-inset-0 tw-flex tw-flex-col;

            header {
                .btn-left {
                    @apply tw-absolute;
                    top: 28px;
                    @include left(24px);
                }

                h1 {
                    @apply tw-text-center;
                }
            }

            .pluginstore-modal-main {
                @apply tw-relative tw-flex tw-flex-grow tw-mb-0 tw-min-h-0;

                .pluginstore-modal-content {
                    @apply tw-overflow-auto tw-flex-grow;
                    padding: 24px;
                }
            }
        }


        /* Payment */

        &.step-payment {
            .blocks {
                @apply tw-flex;
                margin: 0 -20px;

                .block {
                    @apply tw-flex-grow tw-w-1/2;
                    padding: 0 20px;
                }
            }

            .multiselectrow {
                @apply tw-flex;

                & > div {
                    @apply tw-w-1/2;

                    .select {
                        @apply tw-w-full;

                        select {
                            @apply tw-w-full;
                        }
                    }
                }
            }
        }

        /* Thank You */

        &.step-thank-you {
            &.pluginstore-modal .pluginstore-modal-flex .pluginstore-modal-main .pluginstore-modal-content {
                @apply tw-flex tw-p-0 tw-justify-center tw-items-center;
            }

            h2 {
                margin-top: 24px;
            }

            #thank-you-message {
                @apply tw-text-center;
                padding: 48px 24px;
            }
        }
    }
</style>
