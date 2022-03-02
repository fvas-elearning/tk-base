/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2018 Michael Mifsud
 */


(function ($) {
  var statusSelect = function (element, options) {
    // plugin vars
    var defaults = {
      bar: '',
      onFoo: function () {
      }
    };
    var plugin = this;
    var $element = $(element);  // The form group wrapper element
    plugin.settings = {};
    var orgValue = '';
    var select = null;
    var checkbox = null;
    var textarea = null;

    // constructor method
    plugin.init = function () {
      plugin.settings = $.extend({}, defaults, options);

      select = $element.find('select');
      // var checkboxName = select.attr('name') + '_notify';
      // var textareaName = select.attr('name') + '_notes';
      var checkboxName = select.data('notifyName');
      var textareaName = select.data('messageName');
      checkbox = $element.find('input[name="' + checkboxName + '"]');
      textarea = $element.find('textarea[name="' + textareaName + '"]');


      // get the current status value
      orgValue = select.val();
      show();
      select.on('change', show);
      checkbox.on('click', function () {
        if ($(this).prop('checked')) {
          textarea.removeAttr('disabled');
        } else {
          textarea.attr('disabled', 'disabled');
        }
      });
    };  // END init()

    // private methods
    var show = function (e) {
      if (select.val() !== orgValue && select.val() !== '') {
        checkbox.removeAttr('disabled').prop('checked', true);
        textarea.show();
        if (e !== undefined) {
          textarea.focus();
        }
      } else {
        checkbox.attr('disabled', 'disabled');
        textarea.hide();
      }
    };

    // call the "constructor" method
    plugin.init();
  };

  // add the plugin to the jQuery.fn object
  $.fn.statusSelect = function (options) {
    return this.each(function () {
      if (undefined === $(this).data('statusSelect')) {
        var plugin = new statusSelect(this, options);
        $(this).data('statusSelect', plugin);
      }
    });
  }

})(jQuery);