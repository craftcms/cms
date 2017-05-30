<template>
    <div>
        <router-link to="/plugins">Plugins</router-link>
        <hr>
        <router-view></router-view>
    </div>
</template>

<script>
    export default {
        name: 'app'
    }
</script>
