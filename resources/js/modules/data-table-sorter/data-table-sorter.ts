import {DragSort} from '@craftcms/garnish';

// Keeps the `declare const $` seam: the table/rows are passed and measured as
// jQuery by the still-legacy callers (AdminTable) and the drag helper is built
// with jQuery.
declare const $: any;

/**
 * DataTableSorter — a port of `Craft.DataTableSorter` onto `@craftcms/garnish`
 * `DragSort`. Row-reorders a `<table>`'s `<tbody>`, cloning the dragged row into
 * a matching-width helper table. Booted by `Craft.AdminTable` and the modern
 * editable-table module (both via `new Craft.DataTableSorter(...)`), so exposed
 * on `window.Craft`.
 */
// Module-level (a `static defaults` would collide with `DragSort.defaults`,
// a full `DragSortSettings`).
const DEFAULTS = {
  handle: '.move',
  helperClass: 'datatablesorthelper',
};

export class DataTableSorter extends DragSort {
  // DragSort types `settings` as `S | null`; this class treats it loosely.
  declare settings: any;

  $table: any = null;

  constructor(table?: any, settings?: any) {
    const $table = $(table);
    const $rows = $table.children('tbody').children(':not(.filler)');

    super(
      $rows,
      $.extend({}, DEFAULTS, settings, {
        container: $table.children('tbody'),
        caboose: '<tr/>',
        axis: 'y', // Garnish.Y_AXIS
        magnetStrength: 4,
        helperLagBase: 1.5,
      })
    );

    this.$table = $table;
    // `helper` needs `this` (unavailable before super); it's a drag-time
    // callback, so wire it once here.
    this.settings.helper = ($helperRow: any) => this.getHelper($helperRow);
  }

  getHelper($helperRow: any): any {
    const $helper = $(`<div class="${this.settings.helperClass}"/>`).appendTo(
      document.body
    );
    const $table = $('<table/>').appendTo($helper);
    const $tbody = $('<tbody/>').appendTo($table);

    $helperRow.appendTo($tbody);

    // Copy the table width and classes
    $table.width(this.$table.width());
    $table.prop('className', this.$table.prop('className'));

    // Copy the column widths
    const $firstRow = this.$table.find('tr:first');
    const $cells = $firstRow.children();
    const $helperCells = $helperRow.children();

    for (let i = 0; i < $helperCells.length; i++) {
      $($helperCells[i]).width($($cells[i]).width());
    }

    return $helper;
  }
}
