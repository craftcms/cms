<template>
    <div class="plugin-content" @click="$emit('click')">
        <div class="plugin-icon">
            <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="32" />
            <div class="default-icon" v-else></div>
        </div>

        <div>
            <strong>{{ plugin.name }}</strong>
            <div>{{ plugin.shortDescription }}</div>

            <p v-if="plugin.price != '0.00'" class="light">{{ plugin.price|currency }}</p>
            <p class="light" v-else>Free</p>

        </div>
    </div>
</template>

<script>
    export default {
        name: 'pluginCard',
        props: ['plugin'],
    }
</script>

<style scoped>

    .plugin-content {
        border: 1px solid #E3E5E8;
        border-radius: 4px;
        padding: 24px;
        margin: 7px;
        height: 100px;
        display: flex;
    }

    .plugin-content:hover {
        cursor: pointer;
    }

    .plugin-content .plugin-icon {
        margin-right: 14px;
    }

    .plugin-icon, .plugin-icon img { width: 32px; height: 32px; border-radius: 4px; }
    .plugin-icon-large, .plugin-icon-large img { width: 72px; height: 72px; border-radius: 6px; }

    .plugin-icon .default-icon {
        display: inline-block;
        background: #eee;
        overflow: hidden;
        width: 32px;
        height: 32px;
        border-radius: 4px;
    }

</style>