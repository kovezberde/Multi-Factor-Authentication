<?php

/**
 * @file
 * Contains \Drupal\eeremfa\form\EereMfaForm
 * 
 */

namespace Drupal\eereMfa\Form;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;

class EereTokenForm extends FormBase
{
  /*
     * {@inheritdoc}
     */

  public function getFormId()
  {
    return 'eeremfa_token_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $session = \Drupal::request()->getSession();
    $email = $session->get('email', 'default');
    $form_session = $session->get('form_session', 'default');
    $data = EereMfaDB::callExternalDb($email);
    $updatedTokenTypeId =  $data['default_authentication'];
    $soft_token_types = $data['soft_token_types'];

    if (is_int($form_session)) {

      $form['doe'] = array(
        '#markup' => '<div style="background-color: #a6dc4833; padding: 15px; border: 1px solid #ddd; width: 62%"></div>',
      );

      $form['token'] = array(
        '#type' => 'textfield',
        '#title' => t('Token:'),
      );

      if ($updatedTokenTypeId == 4) {
        $value = 'email';
      }
      if ($updatedTokenTypeId == 2) {
        $value = 'sms';
      }
      if ($updatedTokenTypeId == 1) {
        $value = 'auth';
      }

      // $form['defToken'] = array(
      // '#prefix' => '<div class="deftoken-container">Your default authentication method is',
      // '#type' => 'textfield',
      //  '#default_value' => isset($value) ? $value : '',
      //    '#size' => 10,
      //      '#attributes' => array('disabled' => 'disabled'),
      // );

      $form['hfield'] = array(
        '#type' => 'hidden',
        '#default_value' => isset($updatedTokenTypeId) ? $updatedTokenTypeId : '',
        '#size' => 10,
        '#attributes' => array('disabled' => 'disabled'),
      );

      //$form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#prefix' => '<div class="container-inline">',
        array(
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
          '#button_type' => 'primary',
        ),
        array(
          '#type' => 'submit',
          '#value' => $this->t('Cancel'),
          '#submit' => array('eereMfa_cancel'),
        ),
        '#suffix' => '</div><br />',
      );

      $form['suffix'] = array(
        '#markup' => '</div>',
      );

      $form['text'] = array(
        '#markup' => '<div class="dropdown-text"><hr />If you are having difficulties with receiving token number, please request a new token.</div>'
      );
      if ($soft_token_types == 4) {
        $form['my_select'] = [
          '#prefix' => '<div class="select-container-inline">',
          '#type' => 'select',
          '#empty_value' => '',
          '#empty_option' => '-- Select --',
          '#default_value' => (isset($values['my_select']) ? $values['my_select'] : ''),
          '#options' => [
            4 => 'email',
            //2 => 'sms',
            //1 => 'auth'
          ],
        ];
      }

      if ($soft_token_types == 5) {
        $form['my_select'] = [
          '#prefix' => '<div class="select-container-inline">',
          '#type' => 'select',
          '#empty_value' => '',
          '#empty_option' => '-- Select --',
          '#default_value' => (isset($values['my_select']) ? $values['my_select'] : ''),
          '#options' => [
            4 => 'email',
            //2 => 'sms',
            1 => 'auth'
          ],
        ];
      }

      if ($soft_token_types == 6) {
        $form['my_select'] = [
          '#prefix' => '<div class="select-container-inline">',
          '#type' => 'select',
          '#empty_value' => '',
          '#empty_option' => '-- Select --',
          '#default_value' => (isset($values['my_select']) ? $values['my_select'] : ''),
          '#options' => [
            4 => 'email',
            2 => 'sms',
            //1 => 'auth'
          ],
        ];
      }

      if ($soft_token_types == 7) {
        $form['my_select'] = [
          '#prefix' => '<div class="select-container-inline">',
          '#type' => 'select',
          '#empty_value' => '',
          '#empty_option' => '-- Select --',
          '#default_value' => (isset($values['my_select']) ? $values['my_select'] : ''),
          '#options' => [
            4 => 'email',
            2 => 'sms',
            1 => 'auth'
          ],
        ];
      }
      $form['requestAgain'] = array(
        '#type' => 'submit',
        '#value' => t('Request'),
        '#submit' => array('::requestSubmitHandler'),
        '#suffix' => '<hr/>',

      );
      return $form;
    } else {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }
  }


  public function requestSubmitHandler(array &$form, FormStateInterface $form_state)
  {
    $session = \Drupal::request()->getSession();
    $app_user_id = $session->get('app_user_id', 'default');
    $email = $session->get('email', 'default');
    $selectedValue = $form_state->getValues()['my_select'];

    EereMfaToken::requestToken($app_user_id, $selectedValue, $email);
    $form_state->setRebuild(true);
  }



  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $session = \Drupal::request()->getSession();
    $app_user_id = $session->get('app_user_id', 'default');
    $email = $session->get('email', 'default');
    $users = \Drupal::entityTypeManager()->getStorage('user')
      ->loadByProperties(['mail' => $email]);
    $user = reset($users);

    //Vars
    $uid = $user->id();
    //$var = EereMfaDB::callExternalDb($email);
    //$defTokentypeId =  $var['default_authentication'];
    $softToken = $form_state->getValues()['token'];
    $IP = $_SERVER["REMOTE_ADDR"];

    $form_state->setRebuild(true);
    $selectedValue = $form_state->getValues()['my_select'];

    if ($selectedValue == '') {
      $validate = EereMfaToken::validateToken($app_user_id, $form_state->getValues()['hfield'], $softToken, $IP);
    } else {
      $validate = EereMfaToken::validateToken($app_user_id, $selectedValue, $softToken, $IP);
    }

    if ($validate->IsSuccess == 1) {
      $session->set('uid', $uid);
      $redirect = new RedirectResponse(Url::fromUserInput('/user/' . $uid)->toString());
      $request = \Drupal::request();
      $request->getSession()->save();
      $redirect->prepare($request);
      $redirect->send();
    } else {
      \Drupal::messenger()->addMessage(t("Your token is invalid."), 'error');
      #drupal_set_message(t('Your token is invalid.'),'error');
    }
  }

  //  public function requestToken() {
  //   $session = \Drupal::request()->getSession();
  //   $app_user_id = $session->get('app_user_id', 'default');
  //   $defTokentypeId = $session->get('defTokentypeId', 'default');
  //   $email = $session->get('email', 'default');
  //   EereMfaToken::requestToken($app_user_id, $defTokentypeId, $email);

  //}

  public function requestTokenAgain($selectedValue)
  {
    $session = \Drupal::request()->getSession();
    $app_user_id = $session->get('app_user_id', 'default');
    $email = $session->get('email', 'default');
    EereMfaToken::requestTokenAgain($app_user_id, $selectedValue, $email);
  }
}
