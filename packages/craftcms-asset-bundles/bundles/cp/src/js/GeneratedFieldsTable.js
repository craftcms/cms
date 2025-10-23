/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */

Craft.GeneratedFieldsTable = Craft.EditableTable.extend({
  get cvd() {
    return this.$table
      .closest('.field')
      .next('.card-view-designer')
      .data('cvd');
  },

  createRow: function (rowId, columns, baseName, values) {
    const $tr = this.base(rowId, columns, baseName, values);
    $(
      `<input type="hidden" name="${baseName}[${rowId}][uid]" value="${Craft.uuid()}">`
    ).appendTo($tr);
    return $tr;
  },

  createRowObj: function ($tr) {
    return new Craft.GeneratedFieldsTable.Row(this, $tr);
  },
});

Craft.GeneratedFieldsTable.Row = Craft.EditableTable.Row.extend({
  uid: null,

  init: function (table, tr) {
    this.base(table, tr);
    this.uid = this.$tr.find('> input[name$="[uid]"]').val();

    const $nameInput = this.$tr.find('> td:first-child > textarea');
    const $handleInput = this.$tr.find('> td:nth-child(2) > textarea');

    if (!$nameInput.val()) {
      new Craft.HandleGenerator($nameInput, $handleInput);
    }

    this.addListener($nameInput, 'input', () => {
      const name = Craft.trim($nameInput.val());
      const cvd = this.table.cvd;

      if (name !== '') {
        const $draggable = cvd.findCheckboxByUid(this.uid);
        if ($draggable?.length) {
          cvd.updateCheckboxLabel(this.uid, name);
        } else {
          cvd.addCheckbox({
            value: `generatedField:${this.uid}`,
            label: name,
            data: {
              'field-id': this.fieldId,
              'field-label': name,
            },
          });
        }
      } else {
        cvd.removeCheckbox(this.uid);
      }
    });
  },

  destroy: function () {
    this.table.cvd.removeCheckbox(this.uid);
    this.base();
  },
});
