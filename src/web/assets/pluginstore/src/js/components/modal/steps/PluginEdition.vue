<template>
    <step>
        <template slot="header">
            <h1>Choose an edition</h1>
        </template>

        <template slot="main">
            <table v-if="pluginSnippet" class="data fullwidth choose-edition">
                <thead>
                <tr>
                    <th>
                        <div class="plugin-icon">
                            <img v-if="pluginSnippet.iconUrl" :src="pluginSnippet.iconUrl" width="100" height="100" />
                            <img v-else :src="defaultPluginSvg" width="100" height="100" />
                        </div>
                    </th>
                    <td v-for="edition in editions">
                        <div>
                            <edition-badge :name="edition.name"></edition-badge>
                        </div>
                        <div>
                            <template v-if="edition.price > 0">{{edition.price|currency}}</template>
                            <template v-else>Free</template>
                        </div>
                        <div v-if="edition.price > 0">{{edition.renewalPrice|currency}}/year for updates</div>
                        <div v-if="edition.price > 0" class="buttons">
                            <a class="btn submit" @click="addToCart(pluginSnippet, edition.handle)">Add to cart</a>
                        </div>
                    </td>
                </tr>
                </thead>
                <tbody>
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
    import Step from '../Step'
    import EditionBadge from '../../EditionBadge'

    export default {

        props: ['pluginId'],

        data() {
            return {
                loading: false,
                pluginSnippet: null,
            }
        },

        components: {
            Step,
            EditionBadge,
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
                this.pluginSnippet = this.$store.getters['pluginStore/getPluginById'](pluginId)
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

                this.$store.dispatch('cart/addToCart', [item])
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

<style lang="scss" scoped>
    table.choose-edition {
        thead {
            th,
            td {
                padding-bottom: 14px;
            }
        }
    }

    .plugin-icon {
        img {
            max-width: none;
        }
    }
</style>
