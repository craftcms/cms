<template>
    <div v-if="plugin" class="plugin-card" @click="$emit('click')">
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

        props: ['plugin'],

    }
</script>
