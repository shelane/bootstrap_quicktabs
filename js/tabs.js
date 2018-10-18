(function ($, Drupal, drupalSettings) {
  
'use strict';

Drupal.bootstrap_quicktabs = Drupal.bootstrap_quicktabs || {};

Drupal.bootstrap_quicktabs.getQTName = function (el) {
  return el.attr('id').substring(el.attr('id').indexOf('-') + 1);
}

Drupal.behaviors.bootstrap_quicktabs = {
  attach: function (context, settings) {
    $(context).find('div.bootstrap-quicktabs-wrapper').once('bootstrap-quicktabs-wrapper').each(function() {
      var el = $(this);
      Drupal.bootstrap_quicktabs.prepare(el);
    });
  }
}

// Setting up the inital behaviours
Drupal.bootstrap_quicktabs.prepare = function(el) {
  // el.id format: "bootstrap_quicktabs-$name"
  var qt_name = Drupal.bootstrap_quicktabs.getQTName(el);
  var $ul = $(el).find('ul.bootstrap-quicktabs-tabs:first');
  $ul.find('li a').each(function(i, element){
    element.myTabIndex = i;
    element.qt_name = qt_name;
    var tab = new Drupal.bootstrap_quicktabs.tab(element);
    var parent_li = $(element).parents('li').get(0);

    $(element).bind('click', {tab: tab}, Drupal.bootstrap_quicktabs.clickHandler);
  });
}

Drupal.bootstrap_quicktabs.clickHandler = function(event) {
  var tab = event.data.tab;
  var element = this;

  if ($(element).hasClass('use-ajax')) {
    $(element).addClass('quicktabs-loaded');
    $(element).removeClass('use-ajax');
  }

  if (!tab.tabpage.hasClass("tab-pane")) { //TODO: why would it not? was bootstrap_quicktabs-tabpage
    tab = new Drupal.bootstrap_quicktabs.tab(element);
  }

  return false;
}

// TODO: is this trying to build the tabs?
// Constructor for an individual tab
Drupal.bootstrap_quicktabs.tab = function (el) {
  this.element = el;
  this.tabIndex = el.myTabIndex;
  var qtKey = 'qt_' + el.qt_name;
  var i = 0;
  for (var i = 0; i < drupalSettings.bootstrap_quicktabs[qtKey].tabs.length; i++) {
    if (i == this.tabIndex) {
      this.tabObj = drupalSettings.bootstrap_quicktabs[qtKey].tabs[i];
      this.tabKey = i;
    }
  }
  this.tabpage_id = 'quicktabs-tabpage-' + el.qt_name + '-' + this.tabKey;
  this.container = $('#quicktabs-container-' + el.qt_name);
  this.tabpage = this.container.find('#' + this.tabpage_id);
}


if (Drupal.Ajax) {

  /**
   * Handle an event that triggers an AJAX response.
   *
   * This function must be overridden in order to be able to cache loaded tabs, i.e., once a tab
   * content has loaded it should not need to be loaded again. It originally comes from
   * misc/ajax.js.
   *
   * All comments that were in the original core function were removed so that the only
   * only comments inside this function relate to the Bootstrap Quick Tabs modification of it.
   */
  Drupal.Ajax.prototype.eventResponse = function (element, event) {
    event.preventDefault();
    event.stopPropagation();

    // Create a synonym for this to reduce code confusion.
    var ajax = this;

    // Do not perform another Ajax command if one is already in progress.
    if (ajax.ajaxing) {
      return;
    }

    try {
      if (ajax.$form) {
        if (ajax.setClick) {
          element.form.clk = element;
        }

        ajax.$form.ajaxSubmit(ajax.options);
      }
      else {
        if (!$(element).hasClass('quicktabs-loaded')) {
          ajax.beforeSerialize(ajax.element, ajax.options);
          $.ajax(ajax.options);
        }
      }
    }
    catch (e) {
      ajax.ajaxing = false;
      window.alert('An error occurred while attempting to process ' + ajax.options.url + ': ' + e.message);
    }
  };
}

})(jQuery, Drupal, drupalSettings);
