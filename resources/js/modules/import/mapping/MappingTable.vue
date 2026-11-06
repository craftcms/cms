<script setup lang="ts">
  /**
   * A run of destination columns. Rendered by both the map page and the nested
   * panel, which is why it takes the columns and reads everything else from the
   * mapping context around it.
   */
  import '@craftcms/ui/components/info-icon/info-icon';
  import {t} from '@craftcms/ui';
  import MappingRow from './MappingRow.vue';
  import {isMappingColumn, isCompoundMappingColumn} from './paths';
  import type {MappingColumnEntry} from './types';

  defineProps<{
    cols: MappingColumnEntry[];
  }>();
</script>

<template>
  <div class="mapping-table">
    <!-- `cp-table--auto`: plain `cp-table` is `table-layout: fixed` above 840px. -->
    <table class="cp-table cp-table--auto cp-table--padded">
      <thead>
        <tr>
          <th class="mapping-table__destination" scope="col">
            {{ t('Destination') }}
          </th>
          <th scope="col">{{ t('Incoming data') }}</th>
          <th class="mapping-table__option" scope="col">
            {{ t('Match') }}
            <craft-info-icon>{{
              t('Use this field’s value to match against an existing element.')
            }}</craft-info-icon>
          </th>
          <th class="mapping-table__option" scope="col">
            {{ t('Clear') }}
            <craft-info-icon>{{
              t(
                'Clear existing value if no data provided or provided value is empty.'
              )
            }}</craft-info-icon>
          </th>
        </tr>
      </thead>
      <tbody v-for="(entry, index) in cols" :key="index">
        <template v-if="isCompoundMappingColumn(entry)">
          <tr>
            <th colspan="4" scope="colgroup">
              <strong>{{ entry.heading }}</strong>
            </th>
          </tr>
          <MappingRow
            v-for="subfield in entry.subfields"
            :key="subfield.prefixedHandle"
            :col="subfield"
          />
        </template>
        <MappingRow v-else-if="isMappingColumn(entry)" :col="entry" />
      </tbody>
    </table>
  </div>
</template>

<style scoped>
  .mapping-table {
    overflow-x: auto;
  }

  .mapping-table :deep(th) {
    text-align: start;
  }

  .mapping-table__destination {
    width: 20%;
  }

  .mapping-table__option {
    width: 15%;
    white-space: nowrap;
  }
</style>
