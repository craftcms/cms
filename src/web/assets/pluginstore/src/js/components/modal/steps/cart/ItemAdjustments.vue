<template>
  <div>
    <template
      v-for="(adjustment, adjustmentKey) in item.lineItem.adjustments.filter(
        (lineItemAdustment) =>
          lineItemAdustment.sourceSnapshot.type !== 'extendedUpdates'
      )"
    >
      <div :key="itemKey + 'adjustment-' + adjustmentKey">
        <div
          class="tw-py-2 tw-flex tw-border-t tw-border-solid tw-border-gray-200"
        >
          <div class="tw-flex-1">
            <template
              v-if="adjustment.sourceSnapshot.type === 'extendedUpdates'"
            >
              {{
                'Updates until {date}'
                  | t('app', {
                    date: $options.filters.formatDate(
                      adjustment.sourceSnapshot.expiryDate
                    ),
                  })
              }}
            </template>
            <template v-else>
              {{ adjustment.name }}
            </template>
          </div>
          <div class="price tw-w-24 tw-text-right">
            {{ adjustment.amount | currency }}
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
  export default {
    props: {
      item: Object,
    },
  };
</script>
