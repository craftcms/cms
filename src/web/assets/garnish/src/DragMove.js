import BaseDrag from './BaseDrag.js';

/**
 * Drag-to-move clas
 *
 * Builds on the BaseDrag class by simply moving the dragged element(s) along with the mouse.
 */
export default BaseDrag.extend({
  onDrag: function (items, settings) {
    this.$targetItem.css({
      left: this.mouseX - this.mouseOffsetX,
      top: this.mouseY - this.mouseOffsetY,
    });
  },
});
