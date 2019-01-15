<template>
    <div v-if="plugin" class="plugin-card relative tw-flex flex-no-wrap items-start py-6 border-b border-grey-light border-solid" @click="$emit('click')">
        <div class="plugin-icon mr-4">
            <img v-if="plugin.iconUrl" :src="plugin.iconUrl" />
            <img v-else :src="defaultPluginSvg" />
        </div>

        <div>
            <strong>
                {{ plugin.name }}
                <template v-if="trialMode && activeTrialPluginEdition">
                    ({{activeTrialPluginEdition.name}})
                </template>
            </strong>
            <div v-shave="{ height: 45 }">{{ plugin.shortDescription }}</div>
            
            <p class="light">
                <template v-if="priceRange.min !== priceRange.max">
                    <template v-if="priceRange.min > 0">
                        {{priceRange.min|currency}}
                    </template>
                    <template v-else>
                        {{ "Free"|t('app') }}
                    </template>
                    -
                    {{priceRange.max|currency}}
                </template>
                <template v-else>
                    <template v-if="priceRange.min > 0">
                        {{priceRange.min|currency}}
                    </template>
                    <template v-else>
                        {{ "Free"|t('app') }}
                    </template>
                </template>
            </p>

            <div v-if="isPluginInstalled(plugin.handle)" class="installed" data-icon="check"></div>
        </div>
    </div>
</template>

<script>
    import {mapState, mapGetters} from 'vuex'

    export default {

        props: ['plugin', 'trialMode'],

        computed: {

            ...mapState({
                defaultPluginSvg: state => state.craft.defaultPluginSvg,
            }),

            ...mapGetters({
                isPluginInstalled: 'craft/isPluginInstalled',
                getActiveTrialPluginEdition: 'cart/getActiveTrialPluginEdition',
            }),

            activeTrialPluginEdition() {
                return this.getActiveTrialPluginEdition(this.plugin.handle)
            },

            priceRange() {
                const editions = this.plugin.editions

                let min = null
                let max = null

                for(let i = 0; i < editions.length; i++) {
                    const edition = editions[i];
                    const price = parseInt(edition.price)

                    if(min === null) {
                        min = price
                    }

                    if(max === null) {
                        max = price
                    }

                    if(price < min) {
                        min = price
                    }

                    if(price > max) {
                        max = price
                    }
                }

                return {
                    min,
                    max
                }
            }

        },

    }
</script>

<style lang="scss" scoped>
    @import "../../../../../../../lib/craftcms-sass/mixins";

    .plugin-card {
        box-sizing: border-box;

        &:hover {
            @apply .cursor-pointer;

            strong {
                color: $linkColor;
            }
        }

        .plugin-icon {
            img {
                width: 60px;
                height: 60px;
            }
        }

        .installed {
            @apply .absolute;
            top: 14px;
            @include right(18px);
            color: #ccc;
        }
    }

    .ps-grid-plugins {
        .plugin-card {
            @apply .h-full;
        }
    }
</style>