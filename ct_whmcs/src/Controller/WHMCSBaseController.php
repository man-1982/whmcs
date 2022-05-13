<?php


namespace Drupal\ct_whmcs\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\ct_whmcs\Services\WHMCServiceApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WHMCSBaseController extends ControllerBase {

  /**
   * @ var Drupal\ct_whmcs\Services\WHMCServiceApi
   */
  private $whmcs;

  public function __construct(WHMCServiceApi $whmcs) {
    $this->whmcs = $whmcs;




  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ct_whmsc.base')
    );
  }


  public function test(){

    return $this->whmcs->AcceptOrder(10);
  }

}