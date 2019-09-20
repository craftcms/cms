<template>
    <div class="ps-container">
        <h1>{{ "Upgrade Craft CMS"|t('app') }}</h1>
        <hr>
        <cms-editions></cms-editions>
    </div>
</template>

<script>
    import CmsEditions from '../components/upgradecraft/CmsEditions'

    export default {
        components: {
            CmsEditions
        }
    }
</script>
