<?php

namespace Drupal\bootstrap_quicktabs\Plugin\TabRenderer;

use Drupal\quicktabs\TabRendererBase;
use Drupal\quicktabs\Entity\QuickTabsInstance;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Bootstrap Tabs' tab renderer.
 *
 * @TabRenderer(
 *   id = "bootstrap_tabs",
 *   name = @Translation("bootstrap tabs"),
 * )
 */
class BootstrapQuickTabs extends TabRendererBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function optionsForm(QuickTabsInstance $instance) {
    $options = $instance->getOptions()['bootstrap_tabs'];
    $renderer = $instance->getRenderer();
    $form = [];
    $form['ajax'] = array(
      '#type' => 'radios',
      '#title' => t('Ajax'),
      '#options' => array(
        TRUE => t('Yes') . ': ' . t('Load only the first tab on page view'),
        FALSE => t('No') . ': ' . t('Load all tabs on page view.'),
      ),
      '#default_value' => ($renderer == 'bootstrap_tabs' && $options['ajax'] !== NULL) ? $options['ajax'] : 0,
      '#description' => t('Choose how the content of tabs should be loaded.<p>By choosing "Yes", only the first tab will be loaded when the page first viewed. Content for other tabs will be loaded only when the user clicks the other tab. This will provide faster initial page loading, but subsequent tab clicks will be slower. This can place less load on a server.</p><p>By choosing "No", all tabs will be loaded when the page is first viewed. This will provide slower initial page loading, and more server load, but subsequent tab clicks will be faster for the user. Use with care if you have heavy views.</p><p>Warning: if you enable Ajax, any block you add to this quicktabs block will be accessible to anonymous users, even if you place role restrictions on the quicktabs block. Do not enable Ajax if the quicktabs block includes any blocks with potentially sensitive information.</p>'),
      '#weight' => -6,
    );

    $url = Url::fromUri('https://getbootstrap.com/docs/3.3/components/#nav');
    $link = Link::fromTextAndUrl($this->t('Bootstrap'), $url)->toString();
    $form['tabstyle'] = [
      '#default_value' => ($renderer == 'bootstrap_tabs' && isset($options['tabstyle']) && $options['tabstyle']) ? $options['tabstyle'] : 'tabs',
      '#description' => $this->t('Choose the style of tab to use from %bootstrap', ['%bootstrap' => $link]),
      '#options' => [
        'tabs' => $this->t('Tabs'),
        'pills' => $this->t('Pills'),
      ],
      '#title' => $this->t('Style of tabs'),
      '#type' => 'radios',
    ];

    $url = Url::fromUri('https://getbootstrap.com/docs/3.3/components/#nav');
    $link = Link::fromTextAndUrl($this->t('Bootstrap'), $url)->toString();
    $form['tabposition'] = [
      '#default_value' => ($renderer == 'bootstrap_tabs' && isset($options['tabposition']) && $options['tabposition']) ? $options['tabposition'] : 'basic',
      '#description' => $this->t('Choose the position of tab to use from %bootstrap', ['%bootstrap' => $link]),
      '#options' => [
        'basic' => $this->t('Tabs/pills on the top'),
        'left' => $this->t('Tabs/pills on the left'),
        'right' => $this->t('Tabs /pills on the right'),
        'below' => $this->t('Tabs/pills on the bottom'),
        'justified' => $this->t('Tabs/pills justified on the top'),
        'stacked' => $this->t('Tabs/pills stacked'),
      ],
      '#title' => $this->t('Position of tabs'),
      '#type' => 'radios',
    ];

    $url = Url::fromUri('https://getbootstrap.com/docs/3.3/javascript/#tabs-examples');
    $link = Link::fromTextAndUrl($this->t('Bootstrap'), $url)->toString();
    $default_effects = [];
    if ($renderer == 'bootstrap_tabs' && isset($options['tabeffects']) && $options['tabeffects']) {
      $default_effects[] = 'fade';
    }
    $form['tabeffects'] = [
      '#default_value' => $default_effects,
      '#description' => $this->t('Select fade effect on or off %bootstrap', ['%bootstrap' => $link]),
      '#options' => [
        'fade' => $this->t('Fade'),
      ],
      '#title' => $this->t('Effect of tabs'),
      '#type' => 'checkboxes',
    ];
    return $form;
  }

  /**
   * Returns a render array to be used in a block or page.
   *
   * @return array
   *   a render array
   */
  public function render(QuickTabsInstance $instance) {
    $qt_id = $instance->id();
    $type = \Drupal::service('plugin.manager.tab_type');
    $settings = $instance->getOptions()['bootstrap_tabs'];
    $is_ajax = $settings['ajax'];
    $classes = ['nav', 'nav-' . $settings['tabstyle']];
    if ($settings['tabposition'] == 'justified' || $settings['tabposition'] == 'stacked') {
      $classes[] = ' nav-' . $settings['tabposition'];
    }

    $build = [
      '#theme' => 'bootstrap_tabs',
      '#options' => [
        'classes' => implode(' ', $classes),
        'style' => $settings['tabstyle'],
        'position' => $settings['tabposition'],
        'effects' => $settings['tabeffects'],
        'ajax' => $settings['ajax'],
      ],
      '#id' => $qt_id,
    ];
    $build['#theme_wrappers'] = [
      'container' => [
        '#attributes' => [
          'class' => 'bootstrap-quicktabs-wrapper' . ($settings['tabposition'] != 'basic' && $settings['tabposition'] != 'stacked' && $settings['tabposition'] != 'justified' ? ' tabs-' . $settings['tabposition'] : ''),
          'id' => 'quicktabs-container-' . $qt_id,
        ],
      ],
    ];

    // Pages of content that will be shown or hidden.
    $tab_pages = [];

    // Tabs used to show/hide content.
    $titles = [];

    foreach ($instance->getConfigurationData() as $index => $tab) {

      $default_tab = $instance->getDefaultTab() == 9999 ? 0 : $instance->getDefaultTab();
      if (!$is_ajax || $default_tab == $index) {
        $object = $type->createInstance($tab['type']);
        $render = $object->render($tab);
      }
      else {
        $render = ['#markup' => 'Loading content ...'];
      }

      // If user wants to hide empty tabs and there is no content
      // then skip to next tab.
      if ($instance->getHideEmptyTabs() && empty($render)) {
        continue;
      }

      $classes = ['tab-pane'];
      if ($default_tab == $index) {
        $classes[] = 'active';
      }
      if (isset($settings['tabeffects']) && in_array('fade', $settings['tabeffects'], TRUE)) {
        $classes[] = 'fade';
        if ($default_tab == $index) {
          $classes[] = 'in';
        }
      }
      $block_id = 'quicktabs-tabpage-' . $qt_id . '-' . $index;

      $tab_pages[$block_id] = [
        'content' => render($render),
        'classes' => implode(' ', $classes),
      ];

      $link_classes = [];
      if ($default_tab == $index) {
        $link_classes[] = 'quicktabs-loaded active';
      }
      elseif ($is_ajax) {
        $link_classes[] = 'use-ajax';
      }
      else {
        $link_classes[] = 'quicktabs-loaded';
      }
      $titles[$block_id] = [
        'title' => new TranslatableMarkup($tab['title']),
        'classes' => implode(' ', $link_classes),
        'url' => Url::fromRoute(
          'quicktabs.ajax_content',
          [
            'js' => 'nojs',
            'instance' => $qt_id,
            'tab' => $index,
          ]
        )->toString(),
      ];
    }

    $build['#content'] = $tab_pages;
    $build['#tabs'] = $titles;
    $build['#attached'] = [
      'library' => [
        'bootstrap_quicktabs/bootstrap_tabs',
      ],
      'drupalSettings' => [
        'bootstrap_quicktabs' => [
          'qt_' . $qt_id => [
            'tabs' => $tab_pages,
          ],
        ],
      ],
    ];

    return $build;
  }

}
