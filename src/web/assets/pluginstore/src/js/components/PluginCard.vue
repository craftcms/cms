<template>
    <div v-if="plugin" class="plugin-card tw-relative tw-flex tw-flex-no-wrap tw-items-start tw-p-6 tw-border-b tw-border-grey-light tw-border-solid" @click="$emit('click')">
        <div class="plugin-icon">
            <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="32" />
            <img v-else :src="defaultPluginSvg" height="32" />
        </div>

        <div>
            <strong>{{ plugin.name }}</strong>
            <div>{{ plugin.shortDescription }}</div>

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
