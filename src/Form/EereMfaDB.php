<?php

namespace Drupal\eereMfa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class EereMfaDB
{

  public static function callInternalDb($username, $password)
  {
    #$uid = \Drupal::service('user.auth')->authenticate($username, $password);
    \Drupal\Core\Database\Database::setActiveConnection('default');
    $db = \Drupal\Core\Database\Database::getConnection();
    $query = $db->select('users_field_data', 'u');
    $query->addField('u', 'name');

    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
      $query->condition('u.mail', $username);
    } else {
      $query->condition('u.name', $username);
    }

    $results = $query->execute()->fetchObject();

    if ($results) {
      if ($results->name) {
        $uid = \Drupal::service('user.auth')->authenticate($results->name, $password);
        return $uid;
      }
    }
  }

  public static function callExternalDb($email)
  {
    \Drupal\Core\Database\Database::setActiveConnection('external');
    $db = \Drupal\Core\Database\Database::getConnection();
    $query = $db->select('eere_mfa_users', 'u');
    $query->addField('u', 'app_user_id');
    $query->addField('u', 'mail');
    $query->addField('u', 'soft_token_types');
    $query->addField('u', 'default_authentication');
    $query->condition('u.mail', $email);
    $results = $query->execute()->fetchObject();

    if ($results) {
      $output['app_user_id'] = $results->app_user_id;
      $output['mail'] = $results->mail;
      $output['soft_token_types']  = $results->soft_token_types;
      $output['default_authentication']  = $results->default_authentication;
      return $output;
    }
  }

  public static function callRequests($app_user_id)
  {
    $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id)));
    $token_setup = $request->validate_soft_token_setup();
    return $request->validate_soft_token_setup();
  }

  public static function insertExternalDb($username, $email)
  {
    \Drupal\Core\Database\Database::setActiveConnection('external');
    $db = \Drupal\Core\Database\Database::getConnection();
    $result = $db->insert('eere_mfa_users')
      ->fields([
        'username' => $username,
        'mail' => $email,
      ])
      ->execute();
  }

  public static function updateExternalDb($app_user_id, $email)
  {
    $token_setup = EereMfaDB::callRequests($app_user_id);
    \Drupal\Core\Database\Database::setActiveConnection('external');
    $db = \Drupal\Core\Database\Database::getConnection();
    $num_updated = $db->update('eere_mfa_users')
      ->fields([
        'status' => $token_setup->IsCompleted,
        'soft_token_types' => $token_setup->SoftTokenTypes,
        'default_authentication' => $token_setup->DefaultSoftTokenType,
        'created' =>  $token_setup->SetupTime,
        'updated' => $token_setup->SetupTimeUnixTime,
      ])
      ->condition('mail', $email, '=')
      ->execute();
    return true;
  }
}
