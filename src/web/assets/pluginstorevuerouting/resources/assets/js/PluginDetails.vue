<template>
    <div>
        <a @click="openModal()">Open Modal</a>

        <router-link to="/plugins-details/mod">Open Modal with router</router-link>

        <router-view></router-view>
    </div>

</template>

<script>
    import Modal from './Modal.vue'

    export default {
        name: 'pluginDetails',

        components: {
            Modal,
        },
    }
</script>