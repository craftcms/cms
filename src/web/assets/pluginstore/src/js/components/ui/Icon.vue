<template>
    <component class="tw-w-4 tw-h-4 align-middle" :is="computedComponent"></component>
</template>

<script>

import BookIcon from './icons/BookIcon'
import CheckIcon from './icons/CheckIcon'
import ChevronLeftIcon from './icons/ChevronLeftIcon'
import ChevronRightIcon from './icons/ChevronRightIcon'
import CopyIcon from './icons/CopyIcon'
import ExclamationCircleIcon from './icons/ExclamationCircleIcon'
import ExclamationTriangleIcon from './icons/ExclamationTriangleIcon'
import InfoCircleIcon from './icons/InfoCircleIcon'
import LinkIcon from './icons/LinkIcon'
import SearchIcon from './icons/SearchIcon'

export default {
    props: {
        icon: String,
    },
    components: {
        BookIcon,
        CheckIcon,
        ChevronLeftIcon,
        ChevronRightIcon,
        CopyIcon,
        ExclamationCircleIcon,
        ExclamationTriangleIcon,
        InfoCircleIcon,
        LinkIcon,
        SearchIcon,
    },
    computed: {
        computedComponent() {
            return this.icon + '-icon'
        }
    }
}
</script>