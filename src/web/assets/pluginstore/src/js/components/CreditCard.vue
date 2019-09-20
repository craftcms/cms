<template>
    <div class="card">
        <div class="field">
            <cleave class="fullwidth" :class="{error: errors.number}" type="tel" v-model="number" id="cc-number" autocomplete="off" :placeholder="'Card number'|t('app')" :options="{ creditCard: true }"></cleave>
        </div>
        <div class="field">
            <div class="flex">
                <div class="flex-grow">
                    <cleave class="w-full" :class="{error: errors.exp}" type="tel" v-model="exp" id="cc-exp" autocomplete="off" :placeholder="'MM / YY'|t('app')" :options="{ date: true, datePattern: ['m', 'y'] }"></cleave>
                </div>
                <div class="flex-grow">
                    <cleave class="w-full" :class="{error: errors.cvc}" v-model="cvc" id="cc-cvc" autocomplete="off" :placeholder="'CVC'|t('app')" :options="{ numericOnly: true, blocks: [4] }"></cleave>
                </div>
            </div>
        </div>
    </div>
</template>


<script>
    /* global Stripe */

    import {mapState} from 'vuex'
    import Cleave from 'vue-cleave'

    export default {

        components: {
            Cleave,
        },

        data() {
            return {
                number: '',
                exp: '',
                cvc: '',

                errors: {
                    number: false,
                    exp: false,
                    cvc: false,
                }
            }
        },

        computed: {

            ...mapState({
                stripePublicKey: state => state.cart.stripePublicKey,
            }),

            expMonth() {
                const parts = this.exp.split('/')
                return parts[0]
            },

            expYear() {
                const parts = this.exp.split('/')
                return parts[1]
            }

        },

        methods: {

            save(cb, cbError) {
                if (this.validates()) {
                    Stripe.setPublishableKey(this.stripePublicKey)

                    Stripe.source.create({
                        type: 'card',
                        card: {
                            number: this.number,
                            exp_month: this.expMonth,
                            exp_year: this.expYear,
                            cvc: this.cvc,
                        }
                    }, (status, response) => {
                        if (response.error) {
                            cbError(response)
                        } else {
                            cb(response)
                        }
                    })
                } else {
                    cbError()
                }
            },

            validates() {
                let hasErrors = false
                this.errors.number = false
                this.errors.exp = false
                this.errors.cvc = false

                if (!this.number) {
                    this.errors.number = true
                    hasErrors = true
                }

                if (!this.exp) {
                    this.errors.exp = true
                    hasErrors = true
                }

                if (!this.cvc) {
                    this.errors.cvc = true
                    hasErrors = true
                }

                return !hasErrors
            }

        },

    }
</script>