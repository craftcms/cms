<template>
  <component
    class="tw-align-middle tw-inline-block"
    :class="{
      'tw-w-3 tw-h-3': parseInt(size) === 3,
      'tw-w-4 tw-h-4': parseInt(size) === 4,
      'tw-w-5 tw-h-5': parseInt(size) === 5,
      'tw-w-6 tw-h-6': parseInt(size) === 6,
      'tw-w-7 tw-h-7': parseInt(size) === 7,
      'tw-w-8 tw-h-8': parseInt(size) === 8,
      'tw-w-10 tw-h-10': parseInt(size) === 10,
      'tw-w-12 tw-h-12': parseInt(size) === 12,
      'tw-w-16 tw-h-16': parseInt(size) === 16,
    }"
    :is="computedComponent"
  ></component>
</template>

<script>
  import AlertIcon from './icons/AlertIcon';
  import BookIcon from './icons/BookIcon';
  import CheckIcon from './icons/CheckIcon';
  import ChevronLeftIcon from './icons/ChevronLeftIcon';
  import ChevronRightIcon from './icons/ChevronRightIcon';
  import ClipboardCopyIcon from './icons/ClipboardCopyIcon';
  import CopyIcon from './icons/CopyIcon';
  import ExclamationCircleIcon from './icons/ExclamationCircleIcon';
  import ExclamationTriangleIcon from './icons/ExclamationTriangleIcon';
  import ExternalLinkIcon from './icons/ExternalLinkIcon';
  import FlagIcon from './icons/FlagIcon';
  import GithubIcon from './icons/GithubIcon';
  import InfoCircleIcon from './icons/InfoCircleIcon';
  import LinkIcon from './icons/LinkIcon';
  import PlugIcon from './icons/PlugIcon';
  import SearchIcon from './icons/SearchIcon';
  import StarIcon from './icons/StarIcon';
  import WorldIcon from './icons/WorldIcon';
  import XIcon from './icons/XIcon';

  export default {
    props: {
      icon: String,
      size: {
        type: [String, Number],
        default: 4,
      },
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
      ExternalLinkIcon,
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
        return this.icon + '-icon';
      },
    },
  };
</script>
