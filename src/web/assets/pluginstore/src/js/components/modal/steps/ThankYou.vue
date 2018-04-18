<template>
	<step>
		<template slot="main">
			<div id="thank-you-message">
				<div id="graphic" class="spinner big success"></div>
				<h2>{{ "Thank You!"|t('app') }}</h2>
				<p class="light">{{ "Your order has been processed successfully."|t('app') }}</p>
				<p>
					<a :href="managePluginsUrl" class="btn submit">{{ "Manage plugins"|t('app') }}</a>
				</p>
			</div>

			<div id="thank-you-renewals">
				<h2>Auto-renew your licenses</h2>
				<table class="data fullwidth">
					<thead>
					<tr>
						<th></th>
						<th>Item</th>
						<th>Auto Renew</th>
						<th>Price</th>
					</tr>
					</thead>
					<tbody>
					<tr v-for="(item, itemKey) in cartItems">
						<template v-if="item.lineItem.purchasable.type === 'cms-edition'">
							<td class="thin">
								<div class="plugin-icon">
									<img :src="craftLogo" width="32" height="32" />
								</div>
							</td>
							<td>Craft {{ item.lineItem.purchasable.name }}</td>
						</template>

						<template v-else="item.lineItem.purchasable.type === 'plugin-edition'">
							<td class="thin">
								<div class="plugin-icon">
									<img v-if="item.plugin.iconUrl" :src="item.plugin.iconUrl" height="32" />
								</div>
							</td>
							<td>
								{{ item.plugin.name}}
							</td>
						</template>
						<td>
							<lightswitch-input :id="'auto-renew-'+itemKey" v-model="autoRenew[itemKey]" />
						</td>
						<td>
							{{ item.lineItem.purchasable.renewalPrice|currency }}/year
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</template>
	</step>
</template>

<script>
    import {mapState, mapGetters} from 'vuex'

    export default {

        data() {
            return {
                autoRenew: {},
            }
        },

        components: {
            Step: require('../Step'),
            LightswitchInput: require('../../inputs/LightswitchInput'),
        },

        computed: {

            ...mapState({
                craftLogo: state => state.craft.craftLogo,
            }),

            ...mapGetters({
                cartItems: 'cartItems',
            }),

            managePluginsUrl() {
                return Craft.getCpUrl('settings/plugins')
            }

        },

    }
</script>
