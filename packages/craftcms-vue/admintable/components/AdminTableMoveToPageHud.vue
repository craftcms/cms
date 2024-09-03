<template>
  <div ref="move-to-page-modal">
    <div class="last">
      <div class="field">
        <div class="heading">
          <label>{{ heading }}</label>
        </div>
        <div class="input">
          <div class="flex flex-nowrap">
            <div class="select">
              <select v-model="page">
                <option v-for="(p, key) in selectPages" :key="key" :value="p">
                  {{ p }}
                </option>
              </select>
            </div>
            <button type="submit" class="btn submit" tabindex="0">
              <div class="label">{{ moveButtonText }}</div>
              <div class="spinner spinner-absolute"></div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  /* global Craft */
  /* global $ */

  export default {
    name: 'AdminTableMoveToPageHud',

    props: {
      action: String,
      trigger: String,
      pages: Number,
      currentPage: Number,
      moveToPageAction: String,
      perPage: Number,
      reorderSuccessMessage: String,
      ids: Array,
    },

    data() {
      return {
        hud: null,
        page: null,
        heading: Craft.t('app', 'Choose a page'),
        moveButtonText: Craft.t('app', 'Move'),
      };
    },

    computed: {
      selectPages() {
        let pages = [];
        for (let i = 1; i <= this.pages; i++) {
          pages.push(i);
        }

        return pages;
      },
    },

    methods: {
      show() {
        if (!this.hud) {
          this.init();
        }

        this.page = this.currentPage;
        this.hud.show();
      },

      handleSubmit(ev) {
        const id = this.ids[0];
        const data = {
          page: this.page,
          perPage: this.perPage,
          id: id,
        };
        this.$emit('submit');

        Craft.sendActionRequest('POST', this.moveToPageAction, {data})
          .then((response) => {
            Craft.cp.displayNotice(
              Craft.escapeHtml(this.reorderSuccessMessage)
            );

            this.$emit('reload');
          })
          .catch((error) => {
            Craft.cp.displayError(Craft.escapeHtml(error.response.data.error));
            this.$emit('error');
          })
          .finally(() => {
            this.hud.hide();
          });
      },

      init() {
        this.hud = new Garnish.HUD(
          this.trigger,
          this.$refs['move-to-page-modal'],
          {
            showOnInit: false,
            onSubmit: this.handleSubmit,
          }
        );
      },
    },
  };
</script>

<style scoped></style>
