<?php

/**
 * @file
 * Contains \Drupal\eeremfa\form\EereMfaForm
 * 
 */

namespace Drupal\eereMfa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;

class EereMfaForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'eeremfa_login_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $option = \Drupal::config('login_form')->get('option');
    if ($option == 'oneid') {
    return $this->oneIDForm($form, $form_state);
    }
   else {
    return $this->hybridForm($form, $form_state);
  }
 }
 
  public function oneIDForm(array $form, FormStateInterface $form_state) { 
  
    $form['piv'] = array(
     '#type' => 'markup',
     '#markup' => '<div>
       <div class="smart-card-image-wrapper">
        <h2>OneID Sign In</h2><br />
       <img src="' . base_path() . 'modules/custom/eereMfa/assets/oneid.png" alt="DOE OneID" title="DOE OneID Logo"/>
       <button type="button" class="smart-card-btn btn btn-success"><a href="' . base_path() . 'saml/login" title="DOE PIV Card Log In" role="link" class="oneid-link">Login using  OneID (I acknowledge and agree to the "System Terms of Use" and "Privacy Notice" as outlined below)</a></button>
       </div>',
     '#suffix' => '</div>',
    );

    $form['reset'] = array(
      '#markup' => '<div class="oneid-need-help"><p><em>Need some help?</em><br/><a href="' . base_path() . 'user/password" class="login-password" title="Password Reset">Reset your password</a> | <a href="mailto:DL-EEREActioNetWebTeam@ee.doe.gov" title="Help Please!">Contact the site administrator</a></p>',
      '#suffix' => '</div>',
    );

  $form['text'] = array(
      '#markup' => '',
    );

    $form['terms-of-use'] = array(
      '#markup' => '',
    );
       return $form;
  }

  public function hybridForm(array $form, FormStateInterface $form_state) {
  
    $form['text-header'] = array(
      '#markup' => '<h2>Credentials Sign In</h2><br /><div class="intro-text">This site allows login using your site credentials with EERE multifactor authentication or using OneID.</div>'
    );

    $form['username'] = array(
      '#prefix' => '<div class="eere-row"><div>',
      '#type' => 'textfield',
      '#title' => t('Username or Email Address'),
      '#required' => TRUE,
    );

    $form['password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#required' => TRUE,
    );

    $form['actions']['submit'] = array(
      '#prefix' => '<div class="wrapped-button">',
      '#type' => 'submit',
      '#value' => $this->t('Login (I acknowledge and agree to the "System Terms of Use" and "Privacy Notice" as outlined below)'),
      '#button_type' => 'primary',
      '#suffix' => '</div>',
    );

    $form['resetButton'] = array(
      '#type' => 'submit',
      '#value' => t('Reset Token Setup'),
      '#submit' => array('::resetTokenSetupHandler'),
    );

    $form['link'] = array(
      '#markup' => '<a href="#" class="reset button usa-button" title="Reset MFA Token Method">Reset MFA Token Method (Fill in your credentials above)</a>',
    );

    $form['piv'] = array(
     '#type' => 'markup',
     '#markup' => '<div>
       <div class="smart-card-image-wrapper">
        <h2>OneID Sign In</h2><br />
       <img src="' . base_path() . 'modules/custom/eereMfa/assets/oneid.png" alt="DOE OneID" title="DOE OneID Logo"/>
       <button type="button" class="smart-card-btn btn btn-success"><a href="' . base_path() . 'saml/login" title="DOE PIV Card Log In" role="link">Login using  OneID (I acknowledge and agree to the "System Terms of Use" and "Privacy Notice" as outlined below)</a></button>
       </div>',
     '#suffix' => '</div>',
    );

    $form['reset'] = array(
      '#markup' => '<p><em>Need some help?</em><br/><a href="' . base_path() . 'user/password" class="login-password" title="Password Reset">Reset your password</a> | <a href="mailto:DL-EEREActioNetWebTeam@ee.doe.gov" title="Help Please!">Contact the site administrator</a></p>',
      '#suffix' => '</div>',
    );

    $form['text'] = array(
      '#markup' => '
        <div>
        <h3>About Multi-factor Authentication</h3>
        <p>Multi-factor Authentication (MFA) is a required, additional form of verification that works together with your login credentials to prove you really are who you say you are.</p>
        <p>Each time you login to this site, you will receive a unique, single-use code (Token), which you will need to enter when prompted for it during the MFA login process.</p>
        <p>To set this up, you will be redirected to the MFA method setup page the very first time you login. Here you may designate your preferred method to receive these tokens (text message, email, or authenticator application). </p>
        <p>After successfully setting up your prefered method, you will be redirected back to this MFA login page where you may begin the MFA login process.</p>
        </div>',
    '#suffix' => '</div>',
   );

    $form['terms-of-use'] = array(
      '#markup' => '',
    );
      return $form;
  }

  public function resetTokenSetupHandler(array &$form, FormStateInterface $form_state)
  {
    $username = $form_state->getValues()['username'];
    $password = $form_state->getValues()['password'];
    $uid = EereMfaDB::callInternalDb($username, $password);

    if ($uid) {
      $account = \Drupal\user\Entity\User::load($uid); // pass your uid
      $email = $account->getEmail();

      if (EereMfaDB::callExternalDb($email)) {
        $var = EereMfaDB::callExternalDb($email);

        $defTokentypeId =  $var['default_authentication'];
        $app_user_id =  $var['app_user_id'];
        $Url = $_GET['destination'] = getBaseUrl() . 'user/login';
        $IP = $_SERVER["REMOTE_ADDR"];

        $completed = EereMfaDB::callRequests($app_user_id);

        if ($completed->Email != null && $var['mail'] != '') {
          EereMfaDB::updateExternalDb($app_user_id, $email);
          $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'userName' => $username, 'email' => $email, 'userIPAddress' => $IP, 'defaultSoftTokenTypeId' => $defTokentypeId, 'returnUrl' => $Url)));
          $goToUrl = $request->create_soft_token_setup_url();
          $form_state->setResponse(new TrustedRedirectResponse($goToUrl, '302'));
        }
      } else {
        \Drupal::messenger()->addMessage(t("Your MFA account doesn't exist. Please log in first to register."), 'error');
        #drupal_set_message(t("Your MFA account doesn't exist. Please log in first to register."), 'error');
      }
    } else {
      \Drupal::messenger()->addMessage(t('User name or password is invalid.'), 'error');
      #drupal_set_message(t('User name or password is invalid.'),'error');
    }
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
    $username = $form_state->getValues()['username'];
    $password = $form_state->getValues()['password'];
    $uid = EereMfaDB::callInternalDb($username, $password);
    if ($uid) {
      $account = \Drupal\user\Entity\User::load($uid); // pass your uid
      $email = $account->getEmail();

      $var = EereMfaDB::callExternalDb($email);
      // $defTokentypeId =  $var['default_authentication'];
      if (isset($var['app_user_id'])) {
        $app_user_id =  $var['app_user_id'];
      }
      $Url = $_GET['destination'] = getBaseUrl() . 'user/login';
      $IP = $_SERVER["REMOTE_ADDR"];

      //Check if user exists in MFA users table
      if (empty($var)) {
        EereMfaDB::insertExternalDb($username, $email);
        $var = EereMfaDB::callExternalDb($email);
        $app_user_id =  $var['app_user_id'];
        $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'userName' => $username, 'email' => $email, 'userIPAddress' => $IP,  'returnUrl' => $Url)));
        $goToUrl = $request->create_soft_token_setup_url();
        $form_state->setResponse(new TrustedRedirectResponse($goToUrl, '302'));
      }

      $completed = EereMfaDB::callRequests($app_user_id);

      if ($completed->IsCompleted == 0) {
        $request = new EereMfa(array('parameters' => array('appUserId' => $app_user_id, 'userName' => $username, 'email' => $email, 'userIPAddress' => $IP,  'returnUrl' => $Url)));
        $goToUrl = $request->create_soft_token_setup_url();
        $form_state->setResponse(new TrustedRedirectResponse($goToUrl, '302'));
      } else if ($completed->Email != null && $var['mail'] != '') {
        EereMfaDB::updateExternalDb($app_user_id, $email);

        $data = EereMfaDB::callExternalDb($email);
        $updatedTokenTypeId =  $data['default_authentication'];
        $soft_token_types = $data['soft_token_types'];
        EereMfaToken::requestToken($app_user_id, $updatedTokenTypeId, $email);

        $form_state->setRedirect('eeremfa.token');

        //sessions
        $session = \Drupal::request()->getSession();
        $session->set('app_user_id', $app_user_id);
        $session->set('email', $email);
        $session->set('form_session', rand(1, 100000));

        $form_state->setRedirect('eeremfa.token');
      }
    } else {
      \Drupal::messenger()->addMessage(t('User name or password is invalid.'), 'error');
      #drupal_set_message(t('User name or password is invalid.'),'error');
    }
  }
}
