<template>
    <tr>
        <td class="thin">
            <div class="plugin-icon">
                <img v-if="activeTrial.iconUrl" :src="activeTrial.iconUrl" height="40" width="40" />
                <div class="default-icon" v-else></div>
            </div>
        </td>
        <td class="item-name">
            <a :title="activeTrial.name" @click.prevent="navigateToPlugin"><strong>{{ activeTrial.name }}</strong></a>

            <edition-badge v-if="activeTrial.editionName && activeTrial.showEditionBadge" :name="activeTrial.editionName"></edition-badge>
        </td>
        <td>
            <template v-if="activeTrial.price">
                <template v-if="activeTrial.discountPrice">
                    <del class="mr-1">{{activeTrial.price|currency}}</del>
                    <strong>{{(activeTrial.discountPrice)|currency}}</strong>
                </template>
                <template v-else>
                    <strong>{{activeTrial.price|currency}}</strong>
                </template>
            </template>
        </td>
        <td class="w-1/4">
            <div class="text-right">
                <template v-if="!addToCartLoading">
                    <a @click="addToCart()" :loading="addToCartLoading" :class="{
                        'disabled hover:no-underline': activeTrial.licenseMismatched
                    }">{{ "Add to cart"|t('app') }}</a>
                </template>
                <template v-else>
                    <spinner size="sm"></spinner>
                </template>
            </div>
        </td>
    </tr>
</template>

<script>
import EditionBadge from '../../../EditionBadge';

export default {
    components: {EditionBadge},

    props: [
        'activeTrial',
    ],

    data() {
        return {
            addToCartLoading: false,
        }
    },

    methods: {
        addToCart() {
            this.addToCartLoading = true

            const item = {
                type: this.activeTrial.type,
                edition: this.activeTrial.editionHandle,
            }

            if (this.activeTrial.type === 'plugin-edition') {
                item.plugin = this.activeTrial.pluginHandle
            }

            this.$store.dispatch('cart/addToCart', [item])
                .then(() => {
                    this.addToCartLoading = false
                })
                .catch(response => {
                    this.addToCartLoading = false
                    const errorMessage = response.errors && response.errors[0] && response.errors[0].message ? response.errors[0].message : 'Couldn’t add item to cart.';
                    this.$root.displayError(errorMessage)
                })
        },

        navigateToPlugin() {
            const path = this.activeTrial.navigateTo

            this.$root.closeModal()

            if (this.$route.path !== path) {
                this.$router.push({path})
            }
        }
    }
}
</script>