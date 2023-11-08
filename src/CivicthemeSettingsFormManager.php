<?php

namespace Drupal\civictheme;

use Drupal\civictheme\Settings\CivicthemeSettingsFormSectionBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CivicTheme settings form manager.
 */
final class CivicthemeSettingsFormManager implements ContainerInjectionInterface {

  /**
   * Plugin loader.
   */
  protected CivicthemePluginLoader $pluginLoader;

  /**
   * Theme extension list.
   */
  protected ThemeExtensionList $themeExtensionList;

  /**
   * {@inheritdoc}
   */
  public function __construct(CivicthemePluginLoader $plugin_loader, ThemeExtensionList $theme_extension_list) {
    $this->pluginLoader = $plugin_loader;
    $this->themeExtensionList = $theme_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('class_resolver')->getInstanceFromDefinition(CivicthemePluginLoader::class),
      $container->get('extension.list.theme')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\civictheme\Settings\CivicthemeSettingsFormSectionBase[] $sections */
    $sections = $this->pluginLoader->load(
      $this->themeExtensionList->get('civictheme')->getPath() . '/src/Settings',
      CivicthemeSettingsFormSectionBase::class
    );

    // Sort by weight.
    usort($sections, function ($a, $b): int {
      // @phpstan-ignore-next-line
      return strnatcasecmp($a->weight(), $b->weight());
    });

    foreach ($sections as $section) {
      $section->form($form, $form_state);
    }
  }

}
