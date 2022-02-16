<template>
  <component
    class="align-middle"
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
import FlagIcon from './icons/FlagIcon'
import GithubIcon from './icons/GithubIcon'
import InfoCircleIcon from './icons/InfoCircleIcon'
import LinkIcon from './icons/LinkIcon'
import PlugIcon from './icons/PlugIcon'
import SearchIcon from './icons/SearchIcon'
import StarIcon from './icons/StarIcon'
import WorldIcon from './icons/WorldIcon'
import XIcon from './icons/XIcon'

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
    FlagIcon,
    GithubIcon,
    InfoCircleIcon,
    PlugIcon,
    LinkIcon,
    SearchIcon,
    StarIcon,
    WorldIcon,
    XIcon,
  },
  computed: {
    computedComponent() {
      return this.icon + '-icon'
    }
  }
}
</script>