<?php

namespace Drupal\dpl_patron_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dpl_react_apps\Controller\DplReactAppsController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dpl_library_agency\Branch\BranchRepositoryInterface;
use Drupal\dpl_library_agency\BranchSettings;

/**
 * Provides patron page.
 *
 * @Block(
 *   id = "dpl_patron_page_block",
 *   admin_label = "Patron page"
 * )
 */
class PatronPageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * PatronPageBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Drupal config factory to get FBS and Publizon settings.
   * @param \Drupal\dpl_library_agency\BranchSettings $branchSettings
   *   Branch settings.
   * @param \Drupal\dpl_library_agency\Branch\BranchRepositoryInterface $branchRepository
   *   Branch repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, private BranchSettings $branchSettings, private BranchRepositoryInterface $branchRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('dpl_library_agency.branch_settings'),
      $container->get('dpl_library_agency.branch.repository'),
    );
  }

  /**
   * Checks whether the library has enabled text messages.
   *
   * @return bool
   *   True if enabled, false if disabled.
   */
  private function textNotificationsEnabled(): bool {
    $patron_page_settings = $this->configFactory->get('patron_page.settings');
    return !empty($patron_page_settings->get('text_notifications_enabled'));
  }

  /**
   * {@inheritDoc}
   *
   * @return mixed[]
   *   The app render array.
   *
   * @throws \Safe\Exceptions\JsonException
   */
  public function build() {
    $patron_page_settings = $this->configFactory->get('patron_page.settings');
    $general_config = $this->configFactory->get('dpl_library_agency.general_settings');

    $dateConfig = $general_config->get('pause_reservation_start_date_config');
    if (is_null($dateConfig)) {
      $dateConfig = "";
    }

    $data = [
      // Configuration.
      // @todo write service for getting branches.
      'blacklisted-pickup-branches-config' => DplReactAppsController::buildBranchesListProp($this->branchSettings->getExcludedReservationBranches()),
      'branches-config' => DplReactAppsController::buildBranchesJsonProp($this->branchRepository->getBranches()),
      'pincode-length-min-config' => $patron_page_settings->get('pincode_length_min'),
      'pincode-length-max-config' => $patron_page_settings->get('pincode_length_max'),
      'pause-reservation-start-date-config' => $dateConfig,
      'text-notifications-enabled-config' => (int) $this->textNotificationsEnabled(),

      // Urls.
      'pause-reservation-info-url' => $general_config->get('pause_reservation_info_url'),
      'delete-patron-url' => $patron_page_settings->get('delete_patron_url'),
      'always-available-ereolen-url' => $patron_page_settings->get('always_available_ereolen'),

      // Text strings.
      'pause-reservation-modal-aria-description-text' => $this->t('This modal makes it possible to pause your physical reservations', [], ['context' => 'Patron page (aria)']),
      'pause-reservation-modal-header-text' => $this->t('Pause reservations on physical items', [], ['context' => 'Patron page']),
      'pause-reservation-modal-body-text' => $this->t('Pause your reservations early, since reservations that are already being processed, will not be paused.', [], ['context' => 'Patron page']),
      'pause-reservation-modal-close-modal-text' => $this->t('Close pause reservations modal', [], ['context' => 'Patron page']),
      'pause-reservation-modal-below-inputs-text-text' => $this->t('Pause reservation below inputs text', [], ['context' => 'Patron page']),
      'pause-reservation-modal-link-text' => $this->t('Read more', [], ['context' => 'Patron page']),
      'pause-reservation-modal-save-button-label-text' => $this->t('Save', [], ['context' => 'Patron page']),
      'patron-page-header-text' => $this->t('Patron profile page', [], ['context' => 'Patron page']),
      'patron-page-basic-details-header-text' => $this->t('BASIC DETAILS', [], ['context' => 'Patron page']),
      'patron-page-basic-details-name-label-text' => $this->t('Name', [], ['context' => 'Patron page']),
      'patron-page-text-fee-text' => $this->t('patron page text fee text', [], ['context' => 'Patron page']),
      'patron-page-basic-details-address-label-text' => $this->t('Address', [], ['context' => 'Patron page']),
      'patron-contact-info-header-text' => $this->t('CONTACT INFORMATION', [], ['context' => 'Patron page']),
      'patron-contact-info-body-text' => $this->t('patron page contact info body text', [], ['context' => 'Patron page']),
      'patron-contact-phone-label-text' => $this->t('Phone number', [], ['context' => 'Patron page']),
      'patron-contact-phone-checkbox-text' => $this->t('Receive text messages about your loans, reservations, and so forth', [], ['context' => 'Patron page']),
      'patron-contact-email-label-text' => $this->t('E-mail', [], ['context' => 'Patron page']),
      'patron-contact-email-checkbox-text' => $this->t('Receive emails about your loans, reservations, and so forth', [], ['context' => 'Patron page']),
      'patron-page-status-section-header-text' => $this->t('DIGITAL LOANS (EREOLEN)', [], ['context' => 'Patron page']),
      'patron-page-status-section-body-text' => $this->t('There is a number of materials without limitation to amounts of loans per month.', [], ['context' => 'Patron page']),
      'patron-page-status-section-link-text' => $this->t('Click here, to see titles always eligible to be loaned', [], ['context' => 'Patron page']),
      'patron-page-status-section-loan-header-text' => $this->t('Loans per month', [], ['context' => 'Patron page']),
      'patron-page-status-section-loans-ebooks-text' => $this->t('E-books', [], ['context' => 'Patron page']),
      'patron-page-status-section-loans-audio-books-text' => $this->t('Audiobooks', [], ['context' => 'Patron page']),
      'patron-page-change-pickup-header-text' => $this->t('RESERVATIONS', [], ['context' => 'Patron page']),
      'patron-page-change-pickup-body-text' => $this->t('patron page change pickup body text', [], ['context' => 'Patron page']),
      'pickup-branches-dropdown-label-text' => $this->t('Choose pickup branch', [], ['context' => 'Patron page']),
      'pickup-branches-dropdown-nothing-selected-text' => $this->t('Nothing selected', [], ['context' => 'Patron page']),
      'patron-page-pause-reservations-header-text' => $this->t('Pause physical reservations', [], ['context' => 'Patron page']),
      'patron-page-pause-reservations-body-text' => $this->t('patron page pause reservations body text', [], ['context' => 'Patron page']),
      'patron-page-open-pause-reservations-section-text' => $this->t('Pause your reservations', [], ['context' => 'Patron page']),
      'patron-page-open-pause-reservations-section-aria-text' => $this->t('This checkbox opens a section where you can put your current reservations on a pause, when the time period picked has ended, the reservations will be resumed', [], ['context' => 'Patron page (aria)']),
      'date-inputs-start-date-label-text' => $this->t('From', [], ['context' => 'Patron page']),
      'date-inputs-end-date-label-text' => $this->t('To', [], ['context' => 'Patron page']),
      'patron-page-change-pincode-header-text' => $this->t('PINCODE', [], ['context' => 'Patron page']),
      'patron-page-change-pincode-body-text' => $this->t('Change current pin by entering a new pin and saving', [], ['context' => 'Patron page']),
      'patron-page-pincode-label-text' => $this->t('New pin', [], ['context' => 'Patron page']),
      'patron-page-confirm-pincode-label-text' => $this->t('Confirm new pin', [], ['context' => 'Patron page']),
      'patron-pin-saved-success-text' => $this->t('Your pincode was saved', [], ['context' => 'Patron page']),
      'patron-page-pincode-too-short-validation-text' => $this->t('The pincode should be minimum @pincodeLengthMin and maximum @pincodeLengthMax characters long', [], ['context' => 'Patron page']),
      'patron-page-pincodes-not-the-same-text' => $this->t('The pincodes are not the same', [], ['context' => 'Patron page']),
      'patron-page-save-button-text' => $this->t('Save', [], ['context' => 'Patron page']),
      'patron-page-delete-profile-text' => $this->t('Do you wish to delete your library profile?', [], ['context' => 'Patron page']),
      'patron-page-delete-profile-link-text' => $this->t('Delete your profile', [], ['context' => 'Patron page']),
      'patron-page-status-section-reservations-text' => $this->t('You can reserve @countEbooks ebooks and @countAudiobooks audiobooks', [], ['context' => 'Patron page']),
      'patron-page-status-section-out-of-text' => $this->t('@this out of @that', [], ['context' => 'Patron page']),
      'patron-page-status-section-out-of-aria-label-audio-books-text' => $this->t('You used @this audiobooks out of you quota of @that audiobooks', [], ['context' => 'Patron page (aria)']),
      'patron-page-status-section-out-of-aria-label-ebooks-text' => $this->t('You used @this ebooks out of you quota of @that ebooks', [], ['context' => 'Patron page (aria)']),
    ] + DplReactAppsController::externalApiBaseUrls();

    return [
      '#theme' => 'dpl_react_app',
      "#name" => 'patron-page',
      '#data' => $data,
    ];
  }

}