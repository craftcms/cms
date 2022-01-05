/** global: Garnish */
/**
 * Drag-to-move clas
 *
 * Builds on the BaseDrag class by simply moving the dragged element(s) along with the mouse.
 */
Garnish.DragMove = Garnish.BaseDrag.extend(
    {
        onDrag: function(items, settings) {
            this.$targetItem.css({
                left: this.mouseX - this.mouseOffsetX,
                top: this.mouseY - this.mouseOffsetY
            });
        }
    }
);
