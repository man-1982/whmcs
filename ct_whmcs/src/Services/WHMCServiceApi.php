<?php

namespace Drupal\ct_whmcs\Services;

use Drupal\Component\Serialization\Json;use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\PermissionHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;use GuzzleHttp\RequestOptions;

/**
 * Class WHMCServiceApi.
 */
class WHMCServiceApi {

  private $user;
  private $permissionHandler;
  private $configFactory;
  private $url;
  private $responsetype = 'json';
  private $method       = 'POST';
  private $api_identifier;
  private $api_secret;

  /**
   * The client used to send HTTP requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;



  /**
   * Constructs a new WHMCServiceApi object.
   */
  public function __construct(AccountProxy $user, PermissionHandler $permissionHandler, ConfigFactory $configFactory) {
    $this->user                     = $user;
    $this->permissionHandler        = $permissionHandler;
    $this->configFactory            = $configFactory;
    $this->client                   = \Drupal::httpClient();


    $config                         = $this->configFactory->get('ct_whmcs_base.settings');
    $this->api_identifier           = $config->get('api_identifier');
    $this->api_secret               = $config->get('api_secret');
    $this->url                      = $config->get('url');
  }


  /**
 * Add new account to WHMCS
*
* @param \Drupal\user\Entity\User $account
 */
  public function addClient(\Drupal\user\Entity\User $account){
      $action = 'AddClient';
      $field_whmcs_id = $account->field_whmcs_id->value;
      if(!empty($field_whmcs_id)){
        return $this->updateClient($account);
      }
      $request_options = $this->extractedPostFields($account);
      $response = $this->getResponse($request_options, $action);
       if ($response['result'] == 'success' && !empty($response['clientid'])) {
//        $account->activate();
        $account->set('field_whmcs_id', $response['clientid']);
        $account->save();
      }
       if($response['result'] == 'error'){
           \Drupal::logger('ct_whmcs')->error($response['message'] );;
       }

      return $response;
  }

  /**
    * @param \Drupal\user\Entity\User $account
 */

  public function updateClient(\Drupal\user\Entity\User $account){
   $action = 'UpdateClient';
   $request_options = $this->extractedPostFields($account);
   $request_options = $this->extractedPostFields($account);
   $response                        = $this->getResponse($request_options, $action);
   return $response;
  }

  /**
    * @throws \GuzzleHttp\Exception\GuzzleException
 */
  public function getHealthStatus(){
     $action = 'GetHealthStatus';
     $request_options['fetchStatus'] = FALSE;
     $response                        = $this->getResponse($request_options, $action);
  }

  /**
   * Accepts a pending order
   *
   * @param int $orderid
   */
  public function acceptOrder(int $orderid ){
    $action                                     = 'AcceptOrder';
    $request_options['form_params']['orderid']  = $orderid;
    $response         = $this->getResponse($request_options, $action);
    return $response;

  }

  /**
   * Accepts a quote
   *
   * @param int $quoteid
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function acceptQuote(int $quoteid){
    $action           = 'AcceptQuote';
    $request_options['form_params']['quoteid'] = $quoteid;
    $response         = $this->getResponse($request_options, $action);
    return $response;
  }

  /**
   * Activates a given module.
   *
   * @param string $moduleType
   * @param string $moduleName
   * @param array $parameters
   */
  public function activateModule(string $moduleType, string $moduleName, array $parameters = []){
    $action                                       = 'ActivateModule';
    $request_options                              = $this->requestOptionsDefault($action);
    $request_options['form_params']['moduleType'] = $moduleType;
    $request_options['form_params']['moduleName'] = $moduleName;
    $request_options['form_params']['parameters'] = $parameters;
  }







  /**
   * Helper function
   *
   * @param string $action
   *
   * @return array
   */
  public function requestOptionsDefault(string $action): array {
    $request_options['action']       = $action;
    $request_options['responsetype'] = $this->responsetype;
    $request_options['username']     = $this->api_identifier;
    $request_options['password']     = $this->api_secret;

    return $request_options;
  }

  /**
   * @param array $request_options
   *
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getResponse(array $request_options, $action) {
//    $request_options    += $this->requestOptionsDefault($action);
//    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/json';
//    $json_responce = $this->client->request($this->method, $this->url,['form_params' => $request_options, 'http_errors' => false]);
//    $response = (array) Json::decode($json_responce);
//    if($response['result'] == 'error'){
////      TODO Added some handler for error
////      return
//    }
//    return $response;

       // Call the API
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request_options));
      $json_responce = curl_exec($ch);
      curl_close($ch);
      $response = (array) Json::decode($json_responce);

      return $response;

  }

  /**
    * Helper function for extract fields from user account
    * @param \Drupal\user\Entity\User $account
  */
  public function extractedPostFields(\Drupal\user\Entity\User $account):array{
      $postfields = [];
      $postfields['firstname']      = $account->field_first_name->value;
      $postfields['lastname']       = $account->field_last_name->value;
      $postfields['companyname']    = $account->field_company_name->value;
      $postfields['email']          = $account->getEmail();
      $postfields['address1']       = $account->field_place_1->value;
      $postfields['address2']       = $account->field_place_2->value;
      $postfields['city']           = $account->field_city->value;
      $postfields['state']          = $account->field_state_region->value;
      $postfields['postcode']       = $account->field_postcode->value;
      $postfields['country']        = $account->field_country->value; //2 character ISO country code
      //      $postfields['phonenumber']      = $phonenumber;
      $postfields['phonenumber']  = str_replace('+', '', $account->field_mobile_number->value);
      $postfields['password2']    = $account->uuid();

      return $postfields;
}


}
