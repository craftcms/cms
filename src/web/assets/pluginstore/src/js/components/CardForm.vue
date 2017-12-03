<template>
	<form class="stripe-card-form" @submit.prevent="save()">
		<div ref="cardElement" class="stripe-card" :class="{ error: error }"></div>
		<p v-if="error" class="error">{{ error }}</p>

		<!--
		<input type="submit" class="btn btn-primary" value="Save"></input>
		<button type="button" class="btn btn-secondary" @click="cancel()">Cancel</button>
		-->
		<div class="spinner" v-if="loading"></div>
	</form>
</template>


<script>
    export default {

        props: ['loading'],

		data() {
          	return {
          	 	error: null,
			};
		},

        methods: {

            save() {
                this.$emit('beforeSave');
                let vm = this;
                this.stripe.createToken(this.card).then(result => {
                    if (result.error) {
                        vm.error = result.error.message;
                        vm.$emit('error', result.error);
                    } else {
                        vm.$emit('save', vm.card, result.token);
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
            this.stripe = Stripe(window.stripeApiKey);
            this.elements = this.stripe.elements({locale: 'en'});
            this.card = this.elements.create('card');

            // Vue likes to stay in control of $el but Stripe needs a real element
            const el = document.createElement('div')
            this.card.mount(el)

            // this.$children cannot be used because it expects a VNode :(
            this.$refs.cardElement.appendChild(el)
        },

    }
</script>