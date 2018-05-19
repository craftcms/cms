<template>
    <div class="field card">
        <div class="multitext">
            <div class="multitextrow">
                <cleave class="text fullwidth" :class="{error: errors.number}" type="tel" v-model="number" id="cc-number" autocomplete="off" placeholder="Card number" :options="{ creditCard: true }" />
            </div>
            <div class="multitextrow">
                <cleave class="text fullwidth" :class="{error: errors.exp}" type="tel" v-model="exp" id="cc-exp" autocomplete="off" placeholder="MM / YY" :options="{ date: true, datePattern: ['m', 'y'] }" />
                <cleave class="text fullwidth" :class="{error: errors.cvc}" v-model="cvc" id="cc-cvc" autocomplete="off" placeholder="CVC" :options="{ numericOnly: true, blocks: [4] }" />
            </div>
        </div>
    </div>
</template>


<script>
    import {mapState} from 'vuex'

    export default {

        components: {
            TextInput: require('./inputs/TextInput'),
            Cleave: require('vue-cleave'),
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