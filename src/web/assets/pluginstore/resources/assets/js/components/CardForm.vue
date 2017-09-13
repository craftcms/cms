<template>
	<form @submit.prevent="save()">
		<div ref="cardElement" id="card-element" class="form-control mb-3"></div>
		<p id="card-errors" class="text-danger" role="alert"></p>

<!--		<input type="submit" class="btn btn-primary" value="Save"></input>
		<button type="button" class="btn btn-secondary" @click="cancel()">Cancel</button>-->

		<div class="spinner" v-if="loading"></div>
	</form>
</template>


<script>
    export default {
        props: ['loading'],

        methods: {
            save() {
                this.$emit('beforeSave');
                let vm = this;
                this.stripe.createToken(this.card).then(function(result) {
                    if (result.error) {
                        let errorElement = document.getElementById('card-errors');
                        errorElement.textContent = result.error.message;
                        vm.$emit('error', result.error);
                    } else {
                        vm.$emit('save', vm.card, result.token);
                    }
                }).catch(result => {
				});
            },

            cancel() {
                this.card.clear();

                let errorElement = document.getElementById('card-errors');
                errorElement.textContent = '';

                this.$emit('cancel');
            }
        },

        mounted() {
            this.stripe = Stripe('pk_test_B2opWU3D3nmA2QXyHKlIx6so');
            this.elements = this.stripe.elements();
            this.card = this.elements.create('card');

            // Vue likes to stay in control of $el but Stripe needs a real element
            const el = document.createElement('div')
            this.card.mount(el)

            // this.$children cannot be used because it expects a VNode :(
            this.$refs.cardElement.appendChild(el)
        },
    }
</script>