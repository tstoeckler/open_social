<?php

namespace Drupal\social_event_an_enroll_enrolments_export\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_an_enroll\EventAnEnrollManager;
use Drupal\social_event_enrolments_export\Plugin\Action\ExportEnrolments;
use Drupal\social_user_export\Plugin\UserExportPluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exports a event enrollment accounts to CSV.
 *
 * @Action(
 *   id = "social_event_an_enroll_enrolments_export_action",
 *   label = @Translation("Export the selected enrollments to CSV"),
 *   type = "event_enrollment",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event.views_bulk_operations.confirm",
 * )
 */
class ExportAllEnrolments extends ExportEnrolments {

  /**
   * The guest plugin definitions.
   *
   * @var array
   */
  protected $guestPluginDefinitions;

  /**
   * The event an enroll manager.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollManager
   */
  protected $socialEventAnEnrollManager;

  /**
   * Constructs a ExportAllEnrolments object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_user_export\Plugin\UserExportPluginManager $userExportPlugin
   *   The user export plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\social_event_an_enroll\EventAnEnrollManager $social_event_an_enroll_manager
   *   The event an enroll manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UserExportPluginManager $userExportPlugin,
    LoggerInterface $logger,
    EventAnEnrollManager $social_event_an_enroll_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $userExportPlugin, $logger);

    $this->socialEventAnEnrollManager = $social_event_an_enroll_manager;

    $ids = [
      'display_name',
    ];

    foreach ($this->pluginDefinitions as $plugin_id => &$plugin_definition) {
      if ($plugin_definition['provider'] === 'social_user_export' && in_array($plugin_definition['id'], $ids)) {
        unset($this->pluginDefinitions[$plugin_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.user_export_plugin'),
      $container->get('logger.factory')->get('action'),
      $container->get('social_event_an_enroll.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof EventEnrollmentInterface) {
      if ($this->socialEventAnEnrollManager->isGuest($object)) {
        $access = AccessResult::allowed();
      }
      else {
        $access = $this->getAccount($object)->access('view', $account, TRUE);
      }
    }
    else {
      $access = AccessResult::forbidden();
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

}