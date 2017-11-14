<template>
	<div v-if="lastOrder">

		<div id="thank-you">
			<div id="graphic" class="spinner big success"></div>
			<h2>Thank You!</h2>
			<p class="light">Your order has been processed, you will receive an email shortly containing your license keys.</p>
		</div>

		<!--<pre>{{lastOrder}}</pre>-->
	</div>
</template>

<script>
    export default {

		computed: {

		    lastOrder() {
		        return this.$root.lastOrder;
			}

		},

		mounted() {
		    if(!this.lastOrder) {
                this.$router.push({ path: '/'})
			}
		}

    };
</script>