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
import ClipboardCopyIcon from './icons/ClipboardCopyIcon'
import CopyIcon from './icons/CopyIcon'
import ExclamationCircleIcon from './icons/ExclamationCircleIcon'
import ExclamationTriangleIcon from './icons/ExclamationTriangleIcon'
import GithubIcon from './icons/GithubIcon'
import InfoCircleIcon from './icons/InfoCircleIcon'
import LinkIcon from './icons/LinkIcon'
import PlugIcon from './icons/PlugIcon'
import SearchIcon from './icons/SearchIcon'
import StarIcon from './icons/StarIcon'
import WorldIcon from './icons/WorldIcon'

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
    ClipboardCopyIcon,
    CopyIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    GithubIcon,
    InfoCircleIcon,
    PlugIcon,
    LinkIcon,
    SearchIcon,
    StarIcon,
    WorldIcon,
  },
  computed: {
    computedComponent() {
      return this.icon + '-icon'
    }
  }
}
</script>