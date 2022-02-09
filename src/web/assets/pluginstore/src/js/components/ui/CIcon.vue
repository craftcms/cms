<template>
  <component
    class="tw-w-4 tw-h-4 align-middle"
    :is="computedComponent"></component>
</template>

<script>

import AlertIcon from './icons/AlertIcon'
import BookIcon from './icons/BookIcon'
import CheckIcon from './icons/CheckIcon'
import ChevronLeftIcon from './icons/ChevronLeftIcon'
import ChevronRightIcon from './icons/ChevronRightIcon'
import CopyIcon from './icons/CopyIcon'
import ExclamationCircleIcon from './icons/ExclamationCircleIcon'
import ExclamationTriangleIcon from './icons/ExclamationTriangleIcon'
import InfoCircleIcon from './icons/InfoCircleIcon'
import LinkIcon from './icons/LinkIcon'
import PlugIcon from './icons/PlugIcon'
import SearchIcon from './icons/SearchIcon'

export default {
  props: {
    icon: String,
  },
  components: {
    AlertIcon,
    BookIcon,
    CheckIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    CopyIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    InfoCircleIcon,
    PlugIcon,
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