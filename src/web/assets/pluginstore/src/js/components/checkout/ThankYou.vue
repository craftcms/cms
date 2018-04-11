<template>
	<div>

		<div id="thank-you">
			<div id="graphic" class="spinner big success"></div>
			<h2>{{ "Thank You!"|t('app') }}</h2>
			<p class="light">{{ "Your order has been processed successfully."|t('app') }}</p>
			<p>
				<a :href="managePluginsUrl" class="btn submit">{{ "Manage plugins"|t('app') }}</a>
			</p>
		</div>

		<!--<pre v-if="lastOrder">{{lastOrder}}</pre>-->
	</div>
</template>

<script>
    export default {

        computed: {

            lastOrder() {
                return this.$root.lastOrder
            },

            managePluginsUrl() {
                return Craft.getCpUrl('settings/plugins')
            }

        },

        mounted() {
            if (!this.lastOrder) {
                this.$router.push({path: '/'})
            }
        }

    }
</script>
