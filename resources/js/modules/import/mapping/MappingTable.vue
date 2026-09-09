<script setup lang="ts">
    /**
     * A run of destination columns. Rendered by both the map page and the nested
     * panel, which is why it takes the columns and reads everything else from the
     * mapping context around it.
     */
    import MappingRow from './MappingRow.vue';
    import {isCol, isColSet} from './paths';
    import type {MappingColEntry} from './types';

    defineProps<{
        cols: MappingColEntry[];
    }>();
</script>

<template>
    <table>
        <tbody v-for="(entry, index) in cols" :key="index">
            <template v-if="isColSet(entry)">
                <tr>
                    <th colspan="2" scope="colgroup">
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
</template>
