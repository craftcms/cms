<template>
	<div v-if="lastOrder">

		<h2>Thank you!</h2>

		<pre>{{lastOrder}}</pre>

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