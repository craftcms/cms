<script setup lang="ts">
    /**
     * A run of destination columns. Rendered by both the map page and the nested
     * panel, which is why it takes the columns and reads everything else from the
     * mapping context around it.
     */
    import '@craftcms/ui/components/info-icon/info-icon';
    import {t} from '@craftcms/ui';
    import MappingRow from './MappingRow.vue';
    import {isCol, isColSet} from './paths';
    import type {MappingColEntry} from './types';

    defineProps<{
        cols: MappingColEntry[];
    }>();
</script>

<template>
    <div class="mapping-table">
        <!-- `cp-table--auto` matters: plain `cp-table` goes `table-layout: fixed`
         with zero-width cells above 840px, which would split the four columns
         evenly instead of letting the first two take the room they need. -->
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
                            t(
                                'Use this field’s value to match against an existing element.'
                            )
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
                <template v-if="isColSet(entry)">
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
                <MappingRow v-else-if="isCol(entry)" :col="entry" />
            </tbody>
        </table>
    </div>
</template>

<style scoped>
    /* The table can outgrow a slideout panel; scroll it rather than the page. */
    .mapping-table {
        overflow-x: auto;
    }

    .mapping-table__destination {
        width: 20%;
    }

    .mapping-table__option {
        width: 15%;
        white-space: nowrap;
    }
</style>
