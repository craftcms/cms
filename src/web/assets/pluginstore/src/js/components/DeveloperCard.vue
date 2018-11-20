<template>
    <div>
        <div class="avatar inline-block overflow-hidden rounded-full bg-grey mt-4 mb-2 no-line-height">
            <img :src="developer.photoUrl" width="120" height="120" />
        </div>

        <ul>
            <li><strong>{{ developer.developerName }}</strong></li>
            <li>{{ developer.location }}</li>
        </ul>

        <ul class="mt-4">
            <li class="mb-2"><a class="btn block" :href="developer.developerUrl">{{ "Website"|t('app') }}</a></li>
            <li class="mb-2"><a class="btn block" :href="developer.developerUrl">{{ "Contact"|t('app') }}</a></li>
        </ul>
    </div>
</template>

<script>
    export default {
        props: ['developer']
    }
</script>