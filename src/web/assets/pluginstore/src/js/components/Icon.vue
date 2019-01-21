<template>
    <div>
        <font-awesome-icon :icon="icon" :class="cssClass" />
    </div>
</template>

<script>
    export default {
        props: ['icon', 'cssClass'],
    }
</script>