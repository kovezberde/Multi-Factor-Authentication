<?php

namespace Drupal\eereMfa\Form;

use Drupal\Core\Messenger\MessengerInterface;

class EereMfaToken
{

  public static function requestToken($app_user_id, $defTokentypeId, $email)
  {

    if ($defTokentypeId == 4) {
      $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'softTokenTypeId' => $defTokentypeId, 'email' => $email)));
      #drupal_set_message(t('Your token number has been sent. <br/>Please check your email to validate token number.'));
      \Drupal::messenger()->addMessage(t('Your token number has been sent. <br/>Please check your email to validate token number.'));
      return $request->request_soft_token();
    }
    if ($defTokentypeId == 1) {
      $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'softTokenTypeId' => $defTokentypeId, 'email' => $email)));
      #drupal_set_message(t('Please use Google or Microsoft authenticator to validate token number.'));     
      \Drupal::messenger()->addMessage(t('Please use Google or Microsoft authenticator to validate token number.'));
      return $request->request_soft_token();
    }

    if ($defTokentypeId == 2) {
      $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'softTokenTypeId' => $defTokentypeId, 'email' => $email)));
      #drupal_set_message(t('Your token number has been sent. <br/>Please check your phone to validate token number.'));
      \Drupal::messenger()->addMessage(t('Your token number has been sent. <br/>Please check your phone to validate token number.'));
      return $request->request_soft_token();
    }
  }
  public static function validateToken($app_user_id, $defTokentypeId, $softToken, $IP)
  {
    $session = \Drupal::request()->getSession();
    $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'softTokenTypeId' => $defTokentypeId, 'softToken' => $softToken, 'userIPAddress', $IP)));
    return $request->validate_soft_token();
  }

  public static function requestTokenAgain($app_user_id, $selectedValue, $email)
  {
    //Email
    if ($selectedValue == 4) {
      $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'softTokenTypeId' => '4', 'email' => $email)));
      #drupal_set_message(t('Your token number has been sent. <br/>Please check your email to validate token number.'));
      \Drupal::messenger()->addMessage(t('Your token number has been sent. <br/>Please check your email to validate token number.'));
      return $request->validate_soft_token();
    }
    //Auth
    if ($selectedValue == 1) {
      $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'softTokenTypeId' => '1', 'email' => $email)));
      #drupal_set_message(t('Please use Google or Microsoft authenticator to validate token number.'));
      \Drupal::messenger()->addMessage(t('Please use Google or Microsoft authenticator to validate token number.'));
      return $request->validate_soft_token();
    }
    //SMS
    if ($selectedValue == 2) {
      $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'softTokenTypeId' => '2', 'email' => $email)));
      #drupal_set_message(t('Your token number has been sent. <br/>Please check your phone to validate token number.'));
      \Drupal::messenger()->addMessage(t('Your token number has been sent. <br/>Please check your phone to validate token number.'));
      return $request->validate_soft_token();
    }
  }
}
