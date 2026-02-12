<template>
  <div class="tw-p-4 tw-text-center">
    <div class="tw-inline-block">
      <div class="tw-flex tw-items-center">
        <div class="tw-mr-1.5">
          <slot name="icon" />
        </div>
        <div>
          <slot name="count" />
        </div>
      </div>
    </div>
    <div class="tw-mt-1 tw-text-xs tw-text-gray-500">
      <slot name="title" />
    </div>
  </div>
</template>
