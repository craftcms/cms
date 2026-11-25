/* jshint esversion: 6 */
/* globals jQuery */
(function ($) {
  // Position fixed datepickers from the input's viewport rect, since jQuery UI
  // fails to subtract the page scroll when the input's outerHeight() is fractional.
  // See https://github.com/craftcms/cms/issues/20399 and https://bugs.jqueryui.com/ticket/15316/
  const checkOffset = $.datepicker._checkOffset;
  $.datepicker._checkOffset = function (inst, offset, isFixed) {
    if (isFixed && inst.input && inst.input.length) {
      const rect = inst.input[0].getBoundingClientRect();
      offset = {left: rect.left, top: rect.bottom};
    }
    return checkOffset.call(this, inst, offset, isFixed);
  };
})(jQuery);
