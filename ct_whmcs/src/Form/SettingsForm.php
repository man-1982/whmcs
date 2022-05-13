<?php


namespace Drupal\ct_whmcs\Form;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ct_whmcs\Services\WHMCServiceApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends \Drupal\Core\Form\ConfigFormBase {

  // WARNING! dot "." - is very important
  const SETTINGS = 'ct_whmcs_base.settings';

  /**
    * @var \Drupal\ct_whmcs\Services\WHMCServiceApi
  */
  private $whmcsApi;



/**
* SettingsForm constructor.
*
* @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
* @param \Drupal\ct_whmcs\Services\WHMCServiceApi $whmcs_api
 */
  public function __construct(ConfigFactoryInterface $config_factory,  WHMCServiceApi $whmcs_api) {
      parent::__construct($config_factory);
      $this->whmcsApi = $whmcs_api;
  }

    /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ct_whmsc.base')
    );
  }

  /**
   * @param $config
   *
   * @return array
   */
  public function getConfigValues($config): array {
    $url = $config->get('url');
    $api_indetifier = $config->get('api_identifier');
    $api_secret = $config->get('api_secret');
    return array($url, $api_indetifier, $api_secret);
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return [static::SETTINGS,];
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return static::SETTINGS;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Default settings. @see getEditableConfigNames
    $config = $this->config(static::SETTINGS);
    [$url, $api_indetifier, $api_secret] = $this->getConfigValues($config);

    $form['settings_wrapper'] = [
      '#type'       => 'details',
      '#title'      => $this->t('Credential settings for WHMCS'),
      '#description' => $this->t('Set developer credential settings for WHMCS.
       See example http://testbilling.cool-telecom.com/whmcs/admin/configapicredentials.php. 
       Also we need to add server IP to white list on WHMCS. 
       System Settings>General settings>Security Tab API 
       IP Access Restriction'),
      // Open if not set to defaults.
      '#open' => TRUE,

    ];

    $form['settings_wrapper']['url'] = [
      '#type'           => 'url',
      '#title'          => $this->t('WHMCS url adresses'),
      '#description'    => $this->t('Set url for WHMCS server for example https://www.example.com/includes/api.php'),
      '#default_value'  => $url,
    ];
    $form['settings_wrapper']['api_identifier'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('API indetifier'),
      '#description'    => $this->t('Set API indetifier from WHMCS'),
      '#default_value'  => $api_indetifier,
    ];
    $form['settings_wrapper']['api_secret'] = [
      '#type'             => 'textfield',
      '#title'            => $this->t('API secret'),
      '#description'      => $this->t('Set API secret from WHMCS'),
      '#default_value'    => $api_secret,
    ];


    if(!empty($api_secret) && !empty($url) && !empty($api_indetifier)){
      $form['actions']['check_connection'] = [
        '#type'           => 'submit',
        '#value'          => $this->t('Check connection to WHMCS'),
        '#submit'         => ['::checkConnection'],
      ];
    }

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve configuration.
    $url = $form_state->getValue('url');
    $api_indetifier = $form_state->getValue('api_identifier');
    $api_secret = $form_state->getValue('api_secret');
    $this->config(static::SETTINGS)
      //Set the submitted configuration settings
    ->set('url', $url)
    ->set('api_identifier', $api_indetifier)
    ->set('api_secret', $api_secret)
    ->save();
    parent::submitForm($form, $form_state);
  }

  public function checkConnection(array &$form, FormStateInterface $form_state){

//    dpm($form_state);
    $config = $this->config(static::SETTINGS);
    [$url, $api_indetifier, $api_secret] = $this->getConfigValues($config);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,
      http_build_query(
        array(
          'action' => 'GetHealthStatus',
          // See https://developers.whmcs.com/api/authentication
          'username'      => $api_indetifier,
          'password'      => $api_secret,
          'fetchStatus'   => false,
          'responsetype'  => 'json',
        )
      )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    dpm(compact('url', 'api_indetifier', 'api_secret'));
    dpm($response);

    $res = $this->whmcsApi->getHealthStatus();
//    dpm($res);

    if(!empty($response)){

    }

  }

}