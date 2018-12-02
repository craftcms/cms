<template>
    <div v-if="plugin" class="plugin-card relative tw-flex flex-no-wrap items-start py-6 border-b border-grey-light border-solid" @click="$emit('click')">
        <div class="plugin-icon mr-4">
            <img v-if="plugin.iconUrl" :src="plugin.iconUrl" />
            <img v-else :src="defaultPluginSvg" />
        </div>

        <div>
            <strong>{{ plugin.name }}</strong>
            <div v-shave="{ height: 45 }">{{ plugin.shortDescription }}</div>

            <p v-if="plugin.editions[0].price != null && plugin.editions[0].price !== '0.00'" class="light">{{ plugin.editions[0].price|currency }}</p>
            <p class="light" v-else>Free</p>

            <div v-if="isInstalled(plugin)" class="installed" data-icon="check"></div>
        </div>
    </div>
</template>

<script>
    import {mapState, mapGetters} from 'vuex'

    export default {

        props: ['plugin'],

        computed: {

            ...mapState({
                defaultPluginSvg: state => state.craft.defaultPluginSvg,
            }),

            ...mapGetters({
                isInstalled: 'pluginStore/isInstalled',
            }),
        }

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
            right: 18px;
            color: #ccc;
        }
    }

    .ps-grid-plugins {
        .plugin-card {
            @apply .h-full;
        }
    }
</style>