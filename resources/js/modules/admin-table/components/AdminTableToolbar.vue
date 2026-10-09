<template>
  <div class="admin-table-toolbar">
    <div v-if="$slots.status" class="admin-table-toolbar__status">
      <slot name="status"></slot>
    </div>

    <div v-if="$slots.search" class="admin-table-toolbar__search">
      <slot name="search"></slot>
    </div>

    <div v-if="$slots.view" class="admin-table-toolbar__view">
      <slot name="view"></slot>
    </div>

    <div v-if="$slots.actions" class="admin-table-toolbar__actions">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<style scoped lang="postcss">
  .admin-table-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-md);
  }

  .admin-table-toolbar__search {
    flex: 1 1 16rem;
    min-width: 0;
  }

  .admin-table-toolbar__actions {
    display: flex;
    gap: var(--c-spacing-sm);
    margin-inline-start: auto;
  }
</style>
