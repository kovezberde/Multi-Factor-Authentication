<?php

namespace Drupal\eereMfa\Form;

use \SoapClient;

class EereMfa
{

  protected $args;

  public function __construct($args = array())
  {
    //$username = variable_get('username', NUL);
    //$password = variable_get('password', NULL);
    $username = '';
    $password = '';
    //$wsdl = variable_get('url', NULL);
    //$basic = variable_get('basic_url', NULL);
    $wsdl = 'your AuthService wsdl';
    $basic = 'your AuthService basic';
    $this->username = $username;
    $this->password = $password;
    $this->wsdl = $wsdl;
    $this->basic = $basic;

    foreach ($args as $options => $value) {
      $this->options = $value;
    }
  }

  public function _call()
  {
    // SSL Configuration (SSL: Off)
    $context = stream_context_create([
      'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
      ]
    ]);
    $wsse_header = new WsseAuthHeader($this->username, $this->password);
    $client = new SoapClient(
      $this->wsdl,
      array(
        "trace" => 1,
        "exceptions" => 1,
        // SSL Configuration
        'stream_context' => $context,
      )
    );
    $client->__setLocation($this->basic);
    $client->__setSoapHeaders(array($wsse_header));

    return $client;
  }

  function create_soft_token_setup_url()
  {

    $client = $this->_call();

    try {
      $results = $client->CreateSoftTokenSetupUrl($this->options);
    } catch (SoapFault $e) {
      form_set_error('error', t('Error' . $e . $client->__getLastRequest()));
    }

    return $results->CreateSoftTokenSetupUrlResult;
  }


  function request_soft_token()
  {

    $client = $this->_call();

    try {
      $results = $client->RequestSoftToken($this->options);
    } catch (SoapFault $e) {
      form_set_error('error', t('Error' . $e . $client->__getLastRequest()));
    }
    return $results->RequestSoftTokenResult;
    //return $this->options;
  }



  function validate_soft_token()
  {

    $client = $this->_call();

    try {
      $results = $client->ValidateSoftToken($this->options);
    } catch (SoapFault $e) {
      form_set_error('error', t('Error' . $e . $client->__getLastRequest()));
    }
    return $results->ValidateSoftTokenResult;
    //return $this->options;
  }


  function validate_soft_token_setup()
  {

    $client = $this->_call();

    try {
      $results = $client->ValidateSoftTokenSetup($this->options);
    } catch (SoapFault $e) {
      form_set_error('error', t('Error' . $e . $client->__getLastRequest()));
    }
    return $results->ValidateSoftTokenSetupResult;
  }


  function reset_soft_token_setup()
  {

    $client = $this->_call();

    try {
      $client->ResetSoftTokenSetup($this->options);
    } catch (SoapFault $e) {
      form_set_error('error', t('Error' . $e . $client->__getLastRequest()));
    }
  }
}
