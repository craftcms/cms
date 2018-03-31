<template>
	<form class="stripe-card-form" @submit.prevent="save()">
		<div ref="cardElement" class="stripe-card" :class="{ error: error }"></div>
		<p v-if="error" class="error">{{ error }}</p>
		<div class="spinner" v-if="loading"></div>
	</form>
</template>


<script>
    import { mapGetters } from 'vuex'

    export default {

        props: ['loading'],

		data() {
          	return {
          	 	error: null,
			};
		},

        computed: {

            ...mapGetters({
                stripePublicKey: 'stripePublicKey',
            }),
        },

        methods: {

            save(cb, cbError) {
                this.error = null
                this.$emit('beforeSave');
                let vm = this;
                this.stripe.createSource(this.card)
					.then(result => {
						if (result.error) {
							vm.error = result.error.message;
							vm.$emit('error', result.error);
							if(cbError) {
                                cbError();
							}
						} else {
							vm.$emit('save', vm.card, result.source);
							if(cb) {
                                cb();
							}
						}
					});
            },

            cancel() {
                this.card.clear();
                this.error = null;
                this.$emit('cancel');
            }

        },

        mounted() {
            this.stripe = Stripe(this.stripePublicKey);
            this.elements = this.stripe.elements({ locale: 'en' });
            this.card = this.elements.create('card', { hidePostalCode: true });

            // Vue likes to stay in control of $el but Stripe needs a real element
            const el = document.createElement('div')
            this.card.mount(el)

            // this.$children cannot be used because it expects a VNode :(
            this.$refs.cardElement.appendChild(el)
        },

    }
</script>