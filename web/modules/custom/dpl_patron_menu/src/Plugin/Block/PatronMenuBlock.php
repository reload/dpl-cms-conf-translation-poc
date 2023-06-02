<?php

namespace Drupal\dpl_patron_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\dpl_react_apps\Controller\DplReactAppsController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides user intermediate list.
 *
 * @Block(
 *   id = "dpl_patron_menu_block",
 *   admin_label = "Patron menu"
 * )
 */
class PatronMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * PatronMenuBlock constructor.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Drupal config factory to get FBS and Publizon settings.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritDoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param int $plugin_definition
   *   The plugin implementation definition.
   *
   * @return \Drupal\dpl_loans\Plugin\Block\LoanListBlock|static
   *   Loan list block.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritDoc}
   *
   * @return mixed[]
   *   The app render array.
   *
   * @throws \JsonException
   */
  public function build(): array {
    // Alternative to this menu array here this could be loaded from a drupal
    // generated menu. A place for further improvements.
    $menu = [
      [
        "name" => $this->t("Loans", ["context" => 'Patron menu']),
        "link" => Url::fromRoute('dpl_loans.list', [], ['absolute' => TRUE])->toString(),
        "dataId" => "0",
      ],
      [
        "name" => $this->t("Reservations", ["context" => 'Patron menu']),
        "link" => Url::fromRoute('dpl_reservation.list', [], ['absolute' => TRUE])->toString(),
        "dataId" => "10",
      ],
      [
        "name" => $this->t("My list", ["context" => 'Patron menu']),
        "link" => Url::fromRoute('dpl_favorites_list.list', [], ['absolute' => TRUE])->toString(),
        "dataId" => "20",
      ],
      [
        "name" => $this->t("Fees & Replacement costs", ["context" => 'Patron menu']),
        "link" => Url::fromRoute('dpl_fees.list', [], ['absolute' => TRUE])->toString(),
        "dataId" => "30",
      ],
      [
        "name" => $this->t("My account", ["context" => 'Patron menu']),
        "link" => Url::fromRoute('dpl_dashboard.list', [], ['absolute' => TRUE])->toString(),
        "dataId" => "40",
      ],
    ];

    $data = [
      // Config.
      "threshold-config" => $this->configFactory->get('dpl_library_agency.general_settings')->get('threshold_config'),
      "menu-navigation-data-config" => json_encode($menu, JSON_THROW_ON_ERROR),
      // Added in THEME_preprocess_dpl_react_app__menu to use the theme defined
      // icon.
      "profile_svg" => '',

      // Urls.
      "menu-login-url" => Url::fromRoute('dpl_login.login', [], ['absolute' => TRUE])->toString(),
      "menu-log-out-url" => Url::fromRoute('dpl_login.logout', [], ['absolute' => TRUE])->toString(),
      "menu-sign-up-url" => Url::fromRoute('dpl_patron_reg.information', [], ['absolute' => TRUE])->toString(),

      // Texts.
      "menu-notifications-menu-aria-label-text" => $this->t("Notifications menu", [], ["context" => 'Patron menu (Aria)']),
      "menu-user-icon-aria-label-text" => $this->t("Open user menu", [], ["context" => 'Patron menu (Aria)']),
      "menu-profile-links-aria-label-text" => $this->t("Profile links", [], ["context" => 'Profile links (Aria)']),
      "menu-view-your-profile-text" => $this->t("My Account", [], ["context" => 'Patron menu']),
      "menu-notification-loans-expired-text" => $this->t("Loans expired", [], ["context" => 'Patron menu']),
      "menu-notification-loans-expiring-soon-text" => $this->t("Loans expiring soon", [], ["context" => 'Patron menu']),
      "menu-notification-ready-for-pickup-text" => $this->t("Reservations ready for pickup", [], ["context" => 'Patron menu']),
      "menu-notification-ready-for-pickup-url" => $this->t("https://unsplash.com/photos/tNJdaBc-r5c", [], ["context" => 'Patron menu']),
      "menu-notification-loans-expiring-soon-url" => $this->t("https://unsplash.com/photos/tNJdaBc-r5c", [], ["context" => 'Patron menu']),
      "menu-notification-loans-expired-url" => $this->t("https://unsplash.com/photos/tNJdaBc-r5c", [], ["context" => 'Patron menu']),
      "menu-view-your-profile-text-url" => $this->t("https://unsplash.com/photos/tNJdaBc-r5c", [], ["context" => 'Patron menu']),
      "menu-log-out-text" => $this->t("Log out", [], ["context" => 'Patron menu']),
      "menu-login-text" => $this->t("Log in", [], ["context" => 'Patron menu']),
      "menu-sign-up-text" => $this->t("Sign up", [], ["context" => 'Patron menu']),
    ] + DplReactAppsController::externalApiBaseUrls();

    return [
      "#theme" => "dpl_react_app",
      "#name" => "menu",
      "#data" => $data,
    ];
  }

}
