<script setup lang="ts">
  import {FlexRender} from '@tanstack/vue-table';

  defineProps<{
    table: any;
  }>();
</script>

<template>
  <div>
    <div>
      <table>
        <thead>
          <tr
            v-for="headerGroup in table.getHeaderGroups()"
            :key="headerGroup.id"
          >
            <th
              v-for="header in headerGroup.headers"
              :key="header.id"
              :colSpan="header.colSpan"
              :style="{width: `${header.getSize()}px`}"
              class="cell cell--header"
            >
              <FlexRender
                v-if="!header.isPlaceholder"
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in table.getRowModel().rows" :key="row.id">
            <td
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              :style="{width: `${cell.column.getSize()}px`}"
              class="cell"
            >
              <FlexRender
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </td>
          </tr>
        </tbody>
        <!--    <tfoot v-if="table.getFooterGroups()">-->
        <!--      <tr v-for="footerGroup in table.getFooterGroups()" :key="footerGroup.id">-->
        <!--        <th-->
        <!--          v-for="header in footerGroup.headers"-->
        <!--          :key="header.id"-->
        <!--          :colSpan="header.colSpan"-->
        <!--        >-->
        <!--          <FlexRender-->
        <!--            v-if="!header.isPlaceholder"-->
        <!--            :render="header.column.columnDef.footer"-->
        <!--            :props="header.getContext()"-->
        <!--          />-->
        <!--        </th>-->
        <!--      </tr>-->
        <!--    </tfoot>-->
      </table>
    </div>
  </div>
</template>

<style scoped lang="scss">
  table {
    width: 100%;
    border-collapse: collapse;
  }

  .cell {
    padding: var(--c-spacing-md);
  }

  thead tr {
    background-color: var(--c-color-neutral-bg-normal);
  }

  th {
    background-color: var(--c-color-neutral-bg-normal);
    text-align: left;
  }
</style>
