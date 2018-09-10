<template>
    <step>
        <template slot="header">
            <div class="btn-left"><a @click="$emit('back')">← Back</a></div>
            <h1>Choose an edition</h1>
        </template>

        <template slot="main">

            <table v-if="pluginSnippet" class="data fullwidth">
                <thead>
                <tr>
                    <th>Plugin Icon</th>
                    <th v-for="edition in editions">
                        <div>{{edition.name}}</div>
                        <div>
                            <template v-if="edition.price > 0">{{edition.price|currency}}</template>
                            <template v-else>Free</template>
                        </div>
                        <div v-if="edition.price > 0">{{edition.renewalPrice|currency}}/year for updates</div>
                        <div v-if="edition.price > 0" class="buttons">
                            <a class="btn submit" @click="addToCart(pluginSnippet, edition.handle)">Add to cart</a>
                        </div>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th class="group" colspan="4">{{ "Features"|t('app') }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Content Modeling"|t('app') }} <span class="info">{{ "Includes Sections, Global sets, Category groups, Tag groups, Asset volumes, Custom fields, Entry versioning, and Entry drafts"|t('app') }}</span></th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Multi-site Multi-lingual"|t('app') }} <span class="info">{{ "Includes Multiple locales, Section and entry locale targeting, Content translations"|t('app') }}</span></th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Cloud Storage Integration"|t('app') }}</th>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User Accounts"|t('app') }} <span class="info">{{ "Includes User accounts, User groups, User permissions, Public user registration"|t('app') }}</span></th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "System Branding"|t('app') }} <span class="info">{{ "Includes Custom login screen logo, Custom site icon, Custom HTML email template, Custom email message wording"|t('app') }}</span></th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="group" colspan="4">{{ "Support"|t('app') }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Security & Bug Fixes"|t('app') }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Community Support (Slack, Stack Exchange)"|t('app') }}</th>
                    <td></td>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Developer Support"|t('app') }}</th>
                    <td></td>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                </tbody>
            </table>

            <div class="spinner" v-if="loading"></div>
        </template>
    </step>
</template>

<script>
    import * as types from '../../../store/mutation-types'

    export default {

        props: ['pluginId'],

        data() {
            return {
                loading: false,
                pluginSnippet: null,
            }
        },

        components: {
            Step: require('../Step'),
        },

        computed: {
            editions() {
                return [
                    {
                        name: 'Standard',
                        handle: 'standard',
                        price: '19.0000',
                        renewalPrice: '4.0000',
                    },
                    {
                        name: 'Lite',
                        handle: 'lite',
                        price: '99.0000',
                        renewalPrice: '19.0000',
                    },
                    {
                        name: 'Pro',
                        handle: 'pro',
                        price: '199.0000',
                        renewalPrice: '39.0000',
                    }
                ]
            }
        },

        methods: {

            loadPlugin(pluginId) {
                this.loading = true
                this.pluginSnippet = this.$store.getters.getPluginById(pluginId)
                this.$store.commit(types.UPDATE_PLUGIN_DETAILS, null)
                this.$store.dispatch('getPluginDetails', pluginId)
                    .then(response => {
                        this.loading = false
                    })
                    .catch(response => {
                        this.loading = false
                    })
            },

            addToCart(plugin, editionHandle) {
                this.loading = true

                const item = {
                    type: 'plugin-edition',
                    plugin: plugin.handle,
                    edition: editionHandle,
                    autoRenew: false,
                    cmsLicenseKey: window.cmsLicenseKey,
                }

                this.$store.dispatch('addToCart', [item])
                    .then(() => {
                        this.loading = false
                        this.$root.openModal('cart')
                    })
            },


        },

        mounted() {
            this.loadPlugin(this.pluginId)
        }
    }
</script>
