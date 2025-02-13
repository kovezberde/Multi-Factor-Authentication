<?php
namespace Drupal\eereMfa\Routing;

use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Render\RenderCacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \AllowDynamicProperties;

/**
 * Listens to the dynamic route events.
 */
#[AllowDynamicProperties]
class RouteSubscriber implements EventSubscriberInterface {

  public function __construct() {
    $this->account = \Drupal::currentUser();
  }

  public function checkAuthStatus(RequestEvent $event) {
    if ($this->account->isAnonymous() && \Drupal::routeMatch()->getRouteName() == 'user.login') {
      $route_name = \Drupal::routeMatch()->getRouteName();
      if (strpos($route_name, 'eeremfa.login') === 0 && strpos($route_name, 'eeremfa.token') !== FALSE) {
        return;
      }
      $current_path =  \Drupal\Core\Url::fromUserInput('/mfa/login', ['absolute' => TRUE])->toString();
      $response = new RedirectResponse($current_path, 301);
      $response->send();
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkAuthStatus');

    return $events;
  }
}
