/*
 * Plugin: bsConfirm
 * Version: 1.0
 * Date: 11/05/17
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2007 Michael Mifsud
 * @source http://stefangabos.ro/jquery/jquery-plugin-boilerplate-revisited/
 */

/**
 * TODO: Change every instance of "bsConfirm" to the name of your plugin!
 * Description:
 *   {Add a good description so you can identify the plugin when reading the code.}
 *
 * <code>
 *   $(document).ready(function() {
 *     // attach the plugin to an element
 *     $('#element').bsConfirm({'foo': 'bar'});
 *
 *     // call a public method
 *     $('#element').data('bsConfirm').foo_public_method();
 *
 *     // get the value of a property
 *     $('#element').data('bsConfirm').settings.foo;
 *   
 *   });
 * </code>
 */

;(function($) {
  var bsConfirm = function(element, options) {
    var plugin = this;
    plugin.settings = {};
    var $element = $(element);

    // plugin settings
    var defaults = {
      selector: '[data-confirm]',
      btnText: ['Cancel', 'OK'],
      // BS4+ modal template
      modalTemplate: '<div class="modal fade confirm-modal" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">\n' +
        '  <div class="modal-dialog" role="document">\n' +
        '    <div class="modal-content">\n' +
        '      <div class="modal-header">\n' +
        '        <h5 class="modal-title" id="confirmModalLabel">Modal title</h5>\n' +
        '        <button type="button" class="close" data-dismiss="modal" aria-label="Close">\n' +
        '          <span aria-hidden="true">&times;</span>\n' +
        '        </button>\n' +
        '      </div>\n' +
        '      <div class="modal-body"></div>\n' +
        '      <div class="modal-footer">\n' +
        '        <button class="btn btn-danger btn-no" data-dismiss="modal">Cancel</button>\n' +
        '        <button class="btn btn-success btn-yes">OK</button>\n' +
        '      </div>\n' +
        '    </div>\n' +
        '  </div>\n' +
        '</div>',
      onConfirm: function() { },
      onCancel: function() { }
    };

    // plugin vars
    var foo = '';

    // constructor method
    plugin.init = function() {
      plugin.settings = $.extend({}, defaults, $element.data(), options);

      $element.data('confirmed', false);
      $element.on('click', function (e) {
        if ($element.data('confirmed')) {
          // This is here because, returning just true does not make the link work for some reason.
          // I suspect this is due to nested click calls???? For now this will do (it works)
          document.location = $element.attr('href');
          return true;
        }
        $('.confirm-modal').remove();
        var $modal = $(plugin.settings.modalTemplate);
        $modal.find('.modal-title').text($element.attr('title'));
        $modal.find('.modal-body').html($element.data('confirm'));

        $modal.find('.btn-no').text(plugin.settings.btnText[0]).on('click', function () {
          $element.data('confirmed', false);
          $modal.modal('hide');
          return false;
        });
        $modal.find('.btn-yes').text(plugin.settings.btnText[1]).on('click', function () {
          $element.data('confirmed', true);
          $element.trigger('click');
          $modal.modal('hide');
          return true;
        });

        $('body').append($modal);
        $modal.modal();
        $modal.on('hidden.bs.modal', function (e) {
          $modal.remove();
        })
        $modal.modal('show');
        return false;
      });


    };  // END init()

    // private methods
    //var foo_private_method = function() { };

    // public methods
    //plugin.foo_public_method = function() { };

    // call the "constructor" method
    plugin.init();
  };

  // add the plugin to the jQuery.fn object
  $.fn.bsConfirm = function(options) {
    return this.each(function() {
      if (undefined === $(this).data('bsConfirm')) {
        var plugin = new bsConfirm(this, options);
        $(this).data('bsConfirm', plugin);
      }
    });
  }

})(jQuery);
