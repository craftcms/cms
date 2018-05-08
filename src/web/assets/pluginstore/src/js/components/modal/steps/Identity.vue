<template>
	<step>
		<template slot="header">
			<div class="btn-left"><a @click="$emit('back')">← Back</a></div>
			<h1>Identity</h1>
		</template>
		<template slot="main">
			<form @submit.prevent="save()">
				<p><label><input type="radio" value="craftid" v-model="identityMode" /> {{ "Use your Craft ID"|t('app') }}</label></p>

				<template v-if="identityMode === 'craftid'">
					<template v-if="craftId">
						<ul>
							<li>{{ craftId.name }}</li>
							<li>{{ craftId.email }}</li>
						</ul>
						<input type="submit" value="Continue" class="btn submit" :disabled="!validates || loading" :class="{ disabled: !validates || loading }" />
					</template>

					<p v-else><a class="btn submit" @click="connectCraftId">{{ "Connect to your Craft ID"|t('app') }}</a></p>
				</template>

				<p><label><input type="radio" value="guest" v-model="identityMode" /> {{ "Continue as guest"|t('app') }}</label></p>

				<template v-if="identityMode === 'guest'">
					<text-field id="email" placeholder="Email" v-model="guestEmail" :errors="guestEmailError"></text-field>
					<input type="submit" :value="$options.filters.t('Continue', 'app')" class="btn submit" :disabled="!validates || loading" :class="{ disabled: !validates || loading }" />
				</template>

				<div v-if="loading" class="spinner"></div>
			</form>
		</template>
	</step>
</template>

<script>
    import {mapState} from 'vuex'

    export default {

        components: {
            TextField: require('../../fields/TextField'),
            Step: require('../Step'),
        },

        data() {
            return {
                loading: false,
                guestEmail: '',
                guestEmailError: false,
            }
        },

        computed: {

            ...mapState({
                cart: state => state.cart.cart,
                craftId: state => state.craft.craftId,
            }),

            identityMode: {
                get() {
                    return this.$store.state.cart.identityMode
                },

                set(value) {
                    this.$store.commit("CHANGE_IDENTITY_MODE", value)
                }
            },

            validates() {
                if (this.identityMode === 'craftid' && !this.craftId) {
                    return false
                }

                if (this.identityMode === 'guest' && !this.guestEmail) {
                    return false
                }

                return true
            }
        },

        methods: {

            connectCraftId() {
                let width = 800
                let height = 600

                let winWidth = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width
                let winHeight = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height

                let left = ((winWidth / 2) - (width / 2))
                let top = ((winHeight / 2) - (height / 2))

                let url = Craft.getActionUrl('plugin-store/connect', {redirectUrl: Craft.getActionUrl('plugin-store/modal-callback')})
                let name = 'ConnectWithOauth'
                let specs = 'location=0,status=0,width=' + width + ',height=' + height + ',left=' + left + ',top=' + top

                window.open(url, name, specs)
            },

            save() {
                this.loading = true

                if (this.identityMode === 'guest') {
                    let data = {
                        email: this.guestEmail,
                    }

                    this.$store.dispatch('saveCart', data)
                        .then(() => {
                            this.loading = false
                            this.$root.openModal('payment')
                        })
                        .catch(() => {
                            this.loading = false
                            this.$root.displayError("Couldn't save identity.")
                        })
                } else {
                    this.loading = false
                    this.$root.openModal('payment')
                }
            }

        },

        mounted() {
            this.$root.$on('craftIdUpdated', function() {
                if (this.craftId) {
                    this.$root.openModal('payment')
                }
            }.bind(this))

            this.guestEmail = this.cart.email
        }

    }
</script>