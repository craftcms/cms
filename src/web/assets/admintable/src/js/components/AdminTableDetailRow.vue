<template>
  <div>
    <div v-if="rowData.detail.content && (!rowData.detail.showAsList || rowData.detail.showAsList === undefined)" v-html="rowData.detail.content"></div>
    <div v-if="rowData.detail.content && rowData.detail.showAsList">
      <div class="order-flex detail-list" :class="{ 'detail-list-bg': index % 2 }" v-for="key in listKeys" :key="key">
        <div class="detail-list-key">{{key}}:</div>
        <div class="detail-list-value">{{list[key]}}</div>
      </div>
    </div>
  </div>
</template>

<script>
    export default {
        name: 'AdminTableDeleteButton',

        props: {
            rowData: {
                type: Object,
                required: true,
            },
            rowIndex: {
                type: Number
            },
            options: {
                type: Object
            },
            list: {
              type: Object,
              default: function() { return {} }
            },
        },

        data() {
            return {}
        },

        methods: {
            isObject(val) {
                return typeof val === 'object' && !Array.isArray(val);
            },

            addDelimiter(a, b) {
                return a ? `${a}.${b}` : b;
            },

            paths(obj = {}, head = '', depth = 0) {
                if (!obj) {
                    return [];
                }

                Object.entries(obj)
                    .forEach(([key, value]) => {
                        let fullPath = this.addDelimiter(head, key)
                        this.isObject(value) ?
                            this.paths(value, fullPath, depth + 1)
                            : this.list[fullPath] = value;
                    });
            }
        },

        computed: {
            listKeys() {
                return Object.keys(this.list).sort();
            }
        },

        created() {
            this.paths(this.rowData.detail.content);
        }
    }
</script>

<style lang="scss">
  .detail-list {
    padding: 0.2rem 0.5rem;
  }

  .detail-list-bg {
    //background: #f1f5f8;
  }

  .detail-list-key {
    padding-right: 0.25rem;
    font-weight: bold;
  }
</style>