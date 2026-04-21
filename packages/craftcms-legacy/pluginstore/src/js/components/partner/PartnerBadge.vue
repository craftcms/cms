<template>
  <div
    class="tw-border tw-border-blue-600 tw-text-blue-600 tw-rounded-full tw-w-5 tw-h-5 tw-p-[2px]"
  >
    <component :is="computedComponent" class="tw-w-full tw-h-full" />
  </div>
</template>

<script>
  import EnterpriseVerifiedIcon from './icons/EnterpriseVerifiedIcon';
  import CraftCommerceVerifiedIcon from './icons/CraftCommerceVerifiedIcon';
  import CraftVerifiedIcon from './icons/CraftVerifiedIcon';
  export default {
    components: {
      CraftVerifiedIcon,
      CraftCommerceVerifiedIcon,
      EnterpriseVerifiedIcon,
    },
    props: {
      kind: {
        type: String,
        required: true,
      },
    },

    computed: {
      computedComponent() {
        switch (this.kind) {
          case 'craft':
            return 'craft-verified-icon';
          case 'commerce':
            return 'craft-commerce-verified-icon';
          case 'enterprise':
            return 'enterprise-verified-icon';
          default:
            return 'craft-verified-icon';
        }
      },
    },
  };
</script>
