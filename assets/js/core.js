/**
 * The core javascript object for this site
 *
 */

// Include the Uri js object for all pages
//document.write('<script src="'+config.siteUrl+'/vendor/ttek/tk-base/assets/js/sugar.min.js"></script>');
//document.write('<script src="'+config.siteUrl+'/vendor/ttek/tk-base/assets/js/Uri.js"></script>');
// TODO: Add the following to the site instead.

// <script src="/vendor/ttek/tk-base/assets/js/sugar.min.js"></script>
// <script src="/vendor/ttek/tk-base/assets/js/Uri.js"></script>


var project_core = function () {
  "use strict";

  /**
   * enable the sugar utils
   * @link https://sugarjs.com/
   */
  var initSugar = function () {
    if (typeof Sugar === 'undefined') {
      console.warn('Plugin not loaded: Sugar');
      return;
    }
    Sugar.extend();
  };

  /**
   * Creates bootstrap tabs around the \Tk\Form renderer output
   */
  var initTkFormTabs = function () {

    function init() {
      var form = $(this);
      // create bootstrap tab elements around a tabbed form
      form.find('.formTabs').each(function (id, tabContainer) {
        var ul = $('<ul class="nav nav-tabs"></ul>');
        var errorSet = false;

        $(tabContainer).find('.tab-pane').each(function (i, tbox) {
          var name = $(tbox).attr('data-name');
          var li = $('<li class="nav-item"></li>');
          var a = $('<a class="nav-link"></a>');
          a.attr('href', '#' + tbox.id);
          a.attr('data-toggle', 'tab');
          a.text(name);
          li.append(a);

          // Check for errors
          if ($(tbox).find('.has-error, .is-invalid').length) {
            li.addClass('has-error');
          }
          if (i === 0) {
            $(tbox).addClass('active');
            li.addClass('active');
            a.addClass('active');
          }
          ul.append(li);
        });
        $(tabContainer).prepend(ul);
        $(tabContainer).find('li.has-error a');

        //$(tabContainer).find('li.has-error a').tab('show'); // shows last error tab
        $(tabContainer).find('li.has-error a').first().tab('show');   // shows first error tab
      });

      // Deselect tab
      form.find('.formTabs li a').on('click', function (e) {
        $(this).trigger('blur');
      });
    }
    $('form').on('init', document, init).each(init);

  };


  /**
   * Add an edit lock button to text fields
   * So the user has to click the unlock button b4 editing
   */
  var initTkInputLock = function () {
    if ($.fn.tkInputLock === undefined) {
      console.warn('Plugin not loaded: tkInputLock');
      return;
    }

    function init() {
      var form = $(this);
      form.find('input.tk-input-lock').tkInputLock();
    }
    $('form').on('init', document, init).each(init);
  };


  /**
   * Dual select list box renderer
   */
  var initDualListBox = function () {
    //console.warn('TK Plugin DualListBox has been disabled due to errors.');
    if ($.fn.DualListBox === undefined) {
      //console.warn('Plugin not loaded: DualListBox');
      //return;
    }

    function init() {
      var form = $(this);
      // TODO: EMS causes an error here (check other sites with this plugin, time to find another option)??????
      //form.find('select.tk-dualSelect, select.tk-dual-select').DualListBox();
      //form.find('select.tk-dual-select').DualListBox();

      form.find('select.tk-dualSelect, select.tk-dual-select').each(function () {
        var el = $(this);
        el.attr('disabled', 'disabled')
          .after('<p><b>NOTICE: This has been disabled as we are working on a fix for this element.</b></p>');
      });
    }
    $('form').on('init', document, init).each(init);
  };

  /**
   * init the file field renderer
   */
  var initTkFileInput = function () {
    if ($.fn.tkFileInput === undefined) {
      console.warn('Plugin not loaded: tkFileInput');
      return;
    }

    function init() {
      var form = $(this);
      form.find('.tk-imageinput').tkImageInput({dataUrl: config.dataUrl, isBootstrap4: config.isBootstrap4});
      form.find('.tk-multiinput').tkMultiInput({dataUrl: config.dataUrl, isBootstrap4: config.isBootstrap4});
      form.find('.tk-fileinput:not(.tk-imageinput)').tkFileInput({isBootstrap4: config.isBootstrap4});
    }
    $('form').on('init', document, init).each(init);
  };

  /**
   * Init the datetime plugin
   * for single dates and date range fields
   * `.date` = single date text field
   * `.input-datetimerange` = 2 text box range field group
   */
  var initDatetimePicker = function () {
    if ($.fn.datetimepicker === undefined) {
      console.warn('Plugin not loaded: datetimepicker');
      return;
    }

    if (!config.datepickerFormat)
      config.datepickerFormat = 'dd/mm/yyyy';

    function init() {
      var form = $(this);

      // Single Year
      form.find('.year').datetimepicker({
        format: 'yyyy',
        autoclose: true,
        todayBtn: true,
        todayHighlight: true,
        initialDate: new Date(),
        startView: 4,
        minView: 4,
        maxView: 4
      });

      // year Month
      form.find('.yearmonth').datetimepicker({
        format: 'mm/yyyy',
        autoclose: true,
        todayBtn: true,
        todayHighlight: true,
        initialDate: new Date(),
        startView: 4,
        minView: 3,
        maxView: 4
      });

      // single date
      form.find('.date').datetimepicker({
        format: config.datepickerFormat,
        autoclose: true,
        todayBtn: true,
        todayHighlight: true,
        initialDate: new Date(),
        minView: 2,
        maxView: 2
      });

      form.find('.datetime').datetimepicker({
        format: config.datepickerFormat + ' hh:ii',
        minuteStep: 15,
        showMeridian: false,
        showSeconds: false,
        autoclose: true,
        todayBtn: true,
        todayHighlight: true,
        initialDate: new Date()
      });

      form.find('.input-daterange').each(function () {
        // TODO we need to fix the initialDate bug when the date format has the time.
        var inputGroup = $(this);
        var start = inputGroup.find('input').first();
        var end = inputGroup.find('input').eq(1);
        start.datetimepicker({
          todayHighlight: true,
          format: config.datepickerFormat,
          autoclose: true,
          todayBtn: true,
          //initialDate: new Date(),
          initialDate: start.val(),
          minView: 2,
          maxView: 2
        });
        end.datetimepicker({
          todayHighlight: true,
          format: config.datepickerFormat,
          autoclose: true,
          todayBtn: true,
          //initialDate: new Date(),
          initialDate: end.val(),
          minView: 2,
          maxView: 2
        });

        start.datetimepicker().on('changeDate', function (e) {
          //end.datetimepicker('setStartDate', e.date);
          var startDate = start.datetimepicker('getDate');
          var endDate = end.datetimepicker('getDate');
          if (startDate > endDate) {
            end.datetimepicker('setDate', startDate);
          }
        });
        end.datetimepicker().on('changeDate', function (e) {
          //start.datetimepicker('setEndDate', e.date);
          var startDate = start.datetimepicker('getDate');
          var endDate = end.datetimepicker('getDate');
          if (endDate < startDate) {
            start.datetimepicker('setDate', endDate);
          }
        });
      });


      form.find('.input-datetimerange').each(function () {
        var inputGroup = $(this);
        var start = inputGroup.find('input').first();
        var end = inputGroup.find('input').last();
        start.datetimepicker({
          todayHighlight: true,
          format: config.datepickerFormat + ' hh:ii',
          autoclose: true,
          todayBtn: true,
          //startDate: new Date(),
          minuteStep: 5,
          initialDate: start.val()
        });
        end.datetimepicker({
          todayHighlight: true,
          format: 'dd/mm/yyyy hh:ii',
          autoclose: true,
          todayBtn: true,
          //startDate: new Date(),
          minuteStep: 5,
          initialDate: end.val()
        });

        start.datetimepicker().on('changeDate', function (e) {
          //end.datetimepicker('setStartDate', e.date);
          var startDate = start.datetimepicker('getDate');
          var endDate = end.datetimepicker('getDate');
          if (startDate > endDate) {
            end.datetimepicker('setDate', startDate);
          }
        });
        end.datetimepicker().on('changeDate', function (e) {
          //start.datetimepicker('setEndDate', e.date);
          var startDate = start.datetimepicker('getDate');
          var endDate = end.datetimepicker('getDate');
          if (endDate < startDate) {
            start.datetimepicker('setDate', endDate);
          }
        });
      });

    }

    $('form').on('init', document, init).each(init);

  };

  /**
   * Code Mirror setup
   */
  var initCodemirror = function () {
    if (typeof CodeMirror === 'undefined') {
      console.warn('Plugin not loaded: CodeMirror');
      return;
    }
    function init() {
      var form = $(this);
      form.find('textarea.code').each(function () {
        var el = this;
        this.cm = CodeMirror.fromTextArea(this, $.extend({}, {
          lineNumbers: true,
          mode: 'javascript',
          smartIndent: true,
          indentUnit: 2,
          tabSize: 2,
          autoRefresh: true,
          indentWithTabs: false,
          dragDrop: false
        }, $(this).data()));

        $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
          this.refresh();
        }.bind(el.cm));
      });
    }
    $('form').on('init', document, init).each(init);
  };

  /**
   * Tiny MCE setup
   */
  var initTinymce = function (extOpts) {
    if ($.fn.tinymce === undefined) {
      console.warn('Plugin not loaded: jquery.tinymce');
      return;
    }

    /**
     * private elFinder callback function
     * @returns {boolean}
     * @private
     */
    var _elFinderPickerCallback = function (callback, value, meta) {

      tinymce.activeEditor.tkConfig = $.extend({}, config, $(tinymce.activeEditor.getElement()).data());
      tinymce.activeEditor.windowManager.open({
        file: config.siteUrl + '/vendor/ttek/tk-base/assets/js/elFinder/elfinder.html', // use an absolute path!
        title: 'File Manager',
        width: 900,
        height: 430,
        resizable: false
      }, {
        oninsert: function (file, fm) {
          var url, reg, info;
          // URL normalization
          url = fm.convAbsUrl(file.url);
          //url = file.url;
          // Remove domain name from the path
          url = url.replace(url.split('/').slice(0, 3).join('/'), '');


          //console.log(arguments);
          // data-no-rel="data-no-rel"


          // Make file info
          info = file.name;
          // Provide file and text for the link dialog
          if (meta.filetype === 'file') {
            callback(url, {text: info, title: info});
          }
          // Provide image and alt text for the image dialog
          if (meta.filetype === 'image') {
            callback(url, {alt: info});
          }
          // Provide alternative source and posted for the media dialog
          if (meta.filetype === 'media') {
            callback(url);
          }
        }
      });
      return false;
    };

    var mceOpts = {
      theme: 'modern',
      plugins: [
        'advlist autolink link image lists charmap print preview hr anchor',
        'searchreplace code fullscreen insertdatetime media nonbreaking codesample',
        'table directionality emoticons template paste textcolor colorpicker textpattern visualchars visualblocks'
      ],
      toolbar1: 'bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | '+
                'bullist numlist | outdent indent | forecolor backcolor fontselect fontsizeselect',
      toolbar2: 'table | charmap emoticons | link unlink anchor image media | '+
        'hr nonbreaking insertdatetime | print preview | searchreplace removeformat fullscreen preview code codesample',
      toolbar3: '',
      content_css: [
        '//fonts.googleapis.com/css?family=Lato:300,300i,400,400i',
        '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
        config.siteUrl + '/vendor/ttek/tk-base/assets/js/tk-tinymce.css'
      ],
      menubar: true,
      toolbar_items_size: 'small',
      image_advtab: true,
      content_style: 'body {padding: 10px}',
      convert_urls: false,
      baseUrl: config.siteUrl,
      // Trying to fix past wod text issue?????? This does not seem o make any difference, need to find another editor I think
      ////paste_as_text: true,
      //paste_auto_cleanup_on_paste : true,
      //paste_word_valid_elements: "b,strong,i,em,h1,h2,u,p,ol,ul,li,a[href],span,color,font-size,font-color,font-family,mark",
      //paste_retain_style_properties: "all",

      browser_spellcheck: true,
      file_picker_callback: _elFinderPickerCallback,
      setup: function (ed) {
        ed.on('focus', function(e) {
          $(document).trigger('mceFocus');
        });
      }
    };

    function init() {
      var form = $(this);
      form.find('textarea.mce, textarea.mce-med, textarea.mce-min, textarea.mce-micro').each(function () {
        var el = $(this);
        var cfg = {statusbar: false};

        //var readonly = 0;
        if (el.is('[readonly]') || el.is('[disabled]')) {
          cfg.readonly = 1;
          cfg.body_class = 'tk-disabled';
          cfg.toolbar = false;
          cfg.theme_advanced_disable = true;
        }
        var opts = $.extend({}, mceOpts, cfg, extOpts);
        if (el.hasClass('mce-no-fm')) {   // disable the elFinder file manager
          opts.file_picker_callback = null;
        }
        if (el.hasClass('mce-micro')) {
          opts = $.extend({}, {
            plugins: ['lists advlist autolink link image media code'],
            toolbar1: 'bold italic underline strikethrough | alignleft aligncenter alignright ' +
              '| link unlink | removeformat code',
            toolbar2: '',
            toolbar3: '',
            menubar: false
          }, opts);
          opts.height = el.data('height') ? el.data('height') : 200;
        } else if (el.hasClass('mce-min')) {
          opts = $.extend({}, {
            plugins: ['lists advlist autolink link image media code preview fullscreen'],
            toolbar1: 'bold italic underline strikethrough | alignleft aligncenter alignright ' +
              '| bullist numlist | link unlink image media | removeformat fullscreen preview code',
            toolbar2: '',
            toolbar3: ''
          }, opts);
          opts.height = el.data('height') ? el.data('height') : 200;
        } else if (el.hasClass('mce-med')) {
          opts = $.extend({}, {
            //plugins: ['advlist autolink link image lists charmap hr anchor code textcolor colorpicker textpattern'],
            plugins: [
              'advlist autolink link image lists charmap print preview hr anchor',
              'searchreplace code fullscreen insertdatetime media nonbreaking codesample',
              'table directionality emoticons template paste textcolor colorpicker textpattern visualchars visualblocks'
            ],
            toolbar1: 'bold italic underline strikethrough | forecolor backcolor fontsizeselect | alignleft aligncenter alignright ' +
              '| bullist numlist | link unlink image media  | removeformat preview code',
            toolbar2: '',
            toolbar3: '',
            menubar: true
          }, opts);
          opts.height = el.data('height') ? el.data('height') : 400;
          opts.statusbar = true;
        } else {
          opts.statusbar = true;
          opts.height = el.data('height') ? el.data('height') : 500;
        }
        if (el.tinymce())
            el.tinymce().remove();
        el.tinymce(opts);
      });

      // Prevent Bootstrap dialog from blocking focusing
      $(document).on('focusin', function(e) {
        if ($(e.target).closest('.mce-window').length) {
          e.stopImmediatePropagation();
        }
      });
    }
    $('form').on('init', document, init).each(init);

  };




  /**
   * remove focus on menu links
   */
  var initLinkBlur = function () {
    $('body').on('click', 'a[role=tab]', function () {
      $(this).blur();
    });
    //$('a[role=tab]').click(function() { $(this).blur(); });
  };

  /**
   * TODO: use data-confirm
   */
  var initMasqueradeConfirm = function () {
    $('body').on('click', '.tk-msq, .tk-masquerade', function () {
      if (!$(this).is('[data-confirm]'))
        return confirm('You are about to masquerade as the selected user?');
    });
  };

  /**
   * TODO: use data-confirm
   */
  var initTableDeleteConfirm = function () {
    $('body').on('click', '.tk-remove', function () {
      if (!$(this).is('[data-confirm]'))
        return confirm('Are you sure you want to remove this item?');
    });
  };

  /**
   * Now we can have a button confirmation just by adding an attribute
   *  Eg:
   *    <a href="#" class="btn" data-confirm="Are you sure you want to do this?">Delete</a>
   */
  var initDataConfirm = function () {
    if ($.fn.bsConfirm === undefined) {
      $('[data-confirm]').on('click', document, function () {
      //$('body').on('click', '[data-confirm]', function () {
        return confirm($('<p>' + $(this).data('confirm') + '</p>').text());
      });
    } else {
      $('[data-confirm]').bsConfirm({});
    }
  };


  /**
   * Create a standard bootstrap alert box and then add the 'growl' class to the alert div
   * and the alert will react similar to growl type alerts
   */
  var initGrowLikeAlerts = function () {
    // Growl like alert messages that fade out.

    $('.tk-alert-container').each(function () {
      var growlContainer = $('<div class="tk-growl-container"></div>');
      var alertContainer = $(this);
      alertContainer.before(growlContainer);

      growlContainer.updateAlerts = function () {
        alertContainer.find('.alert.growl').each(function () {
          var alert = $(this);
          alert.detach().appendTo(growlContainer);
        });

        $(this).find('.alert').not('.hiding').each(function () {
          var a = $(this);
          $(this).addClass('hiding');
          setTimeout(function () {
            a.fadeOut(1000, function() { $(this).removeClass('hiding'); $(this).remove(); });
          }, 4000);
        });
      };
      growlContainer.updateAlerts();



      // TODO: make this a plugin so we can dynamically add the alerts from other scripts
      function addAlert(msg, type) {
        var alert = $('<div class="alert alert-'+type+' growl">\n' +
          '    <button class="close noblock" data-dismiss="alert">&times;</button>\n' +
          //'    <h4><i choice="icon" var="icon"></i> <strong var="title">This is a test</strong></h4>\n' +
          '    <span>'+msg+'</span>\n' +
          '  </div>');
        growlContainer.append(alert);
        growlContainer.updateAlerts();
      }
      // setTimeout(function () {
      //   addAlert('This is a test message', 'info');
      // }, 1000);
    });

  };


  /**
   * Create a bootstrap 3 panel around a div. Update the template to add your own panel
   * Div Eg:
   *   <div class="tk-panel" data-panel-title="Panel Title" data-panel-icon="fa fa-building-o"></div>
   *
   */
  var initTkPanel = function () {

    if (config.tkPanel === undefined) {
      config.tkPanel = {};
    }
    if (config.tkPanel.template === undefined) {
      config.tkPanel.template =
        '<div class="panel panel-default">\n' +
        '  <div class="panel-heading"><i class="tp-icon"></i> <span class="tp-title"></span></div>\n' +
        '  <div class="tp-body panel-body"></div>\n' +
        '</div>';
    }

    $('.tk-panel').each(function () {
      var element = $(this);
      element.hide();
      var defaults = {
        panelTemplate: config.tkPanel.template
      };
      var settings = $.extend({}, defaults, element.data());
      if (settings.panelTitle === undefined && $('.page-header').length) {
        if ($('.page-header .page-title').length) {
          settings.panelTitle = $('.page-header .page-title').text();
        } else {
          settings.panelTitle = $('.page-header').text();
        }
      }
      var tpl = $(settings.panelTemplate);
      tpl.addClass(element.attr('class'));
      element.attr('class', 'tk-panel-org');

      tpl.hide();
      if (settings.panelIcon !== undefined) {
        tpl.find('.tp-icon').addClass(settings.panelIcon);
      }
      if (settings.panelTitle !== undefined) {
        tpl.find('.tp-title').text(settings.panelTitle);
      }
      if (element.find('.tk-panel-title-right')) {
        element.find('.tk-panel-title-right').addClass('pull-right float-right');
        tpl.find('.tp-title').parent().append(element.find('.tk-panel-title-right'));
        //tpl.find('.tp-title').parent().parent().append(element.find('.tk-panel-title-right'));
      }
      element.before(tpl);
      element.detach();
      tpl.find('.tp-body').append(element);
      element.show();
      tpl.show();

    });
  };

  /**
   *
   */
  var initDataToggle = function () {

    $('[data-toggle="hide"]').each(function () {
      var el = $(this);
      var target = $(el.data('target'));
      target.each(function() {
        $(this).hide();
      });
      el.on('click', function () {
        target.toggle();
      })
    });
    $('[data-toggle="show"]').each(function () {
      var el = $(this);
      var target = $(el.data('target'));
      target.each(function() {
        $(this).show();
      });
      el.on('click', function () {
        target.toggle();
      })
    });
  };

  var initTkTable = function () {
    $('.tk-table .tk-filters .help-block.error-block').remove();
    $('.tk-table .tk-filters .form-group > label').remove();

  };


  return {
    initSugar: initSugar
    , initDatetimePicker: initDatetimePicker
    , initLinkBlur: initLinkBlur
    , initTkFileInput: initTkFileInput
    , initTkInputLock: initTkInputLock
    , initDualListBox: initDualListBox
    , initCodemirror: initCodemirror
    , initTinymce: initTinymce
    , initMasqueradeConfirm: initMasqueradeConfirm
    , initTableDeleteConfirm: initTableDeleteConfirm
    , initGrowLikeAlerts: initGrowLikeAlerts
    , initTkPanel: initTkPanel
    , initDataToggle: initDataToggle
    , initTkFormTabs: initTkFormTabs
    , initDataConfirm: initDataConfirm
    , initTkTable: initTkTable
  }

}();




