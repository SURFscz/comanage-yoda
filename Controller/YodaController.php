<?php
/**
 * COmanage Registry Yoda specific Controller
 *
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("AppController", "Controller");

class YodaController extends AppController {
  // Class name, used by Cake
  public $name = "Yoda";
  public $components=array('Yoda.ServiceTokenMailer');

  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'name' => 'asc'
    )
  );

  public $requires_co=true;

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    $this->copersonid=null;
    if(isset($roles['copersonid']))
    {
        $this->copersonid = $roles['copersonid'];
    }

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Configure the page settings
    $p['index'] = $roles['coadmin'] || $roles['cmadmin'];
    $p['reset'] = !empty($roles['copersonid']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

 /**
   * Parse the named co parameter
   *
   * @return Integer The CO ID if found, or -1 if not
   */

  public function parseCOID($data = null) {
    if(in_array($this->action,array('index','reset'))) 
    {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }

    return parent::parseCOID();
  }

 /**
   * Redirect to the CoServiceToken generate page for the current user
   */
  
  public function reset()
  {
    if(!empty($this->copersonid)) 
    {
      $coid = $this->cur_co['Co']['id'];
      $yoda=$this->ServiceTokenMailer->getYodaConfig($coid);

      if(!empty($yoda['CoServiceTokenSetting'])) 
      {
        $this->redirect(array(
          "plugin" => "co_service_token",
          "controller" => "co_service_tokens",
          "action" => "generate",
          "tokensetting" => $yoda['CoServiceTokenSetting']['id'],
          "copersonid" => $this->copersonid,
        ));
      }
      return true;
    }

    // do not support features for non-members of the current CO
    $this->Flash->set(_txt('er.permission'), array('key' => 'error'));
    $this->redirect("/");
  } 

  public function index()
  {
      $coid = $this->cur_co['Co']['id'];

      $args=array();
      $args['conditions']['Yoda.co_id'] = $coid;
      $args['contain']=array('CoMessageTemplate','CoService');
      $yoda=$this->Yoda->find('first',$args);

      if($this->request->is('post'))
      {
          try 
          {
              $data = $this->request->data;

              // link the Yoda and CO instance
              if(isset($yoda['Yoda']['id']))
              {
                  $data['Yoda']['id']=$yoda['Yoda']['id'];
              }

              $ret = $this->Yoda->save($data);
              if(!empty($ret))
              {
                  $yoda=array_merge($yoda,$ret);
              }
          }
          catch(Exception $e)
          {
              $err = filter_var($e->getMessage(),FILTER_SANITIZE_SPECIAL_CHARS);
              $this->Flash->set($err ?: _txt('er.fields'), array('key' => 'error'));
          }
      }

      // Set View variables

      $this->set('yoda',$yoda);

      // Pending the ability to add dynamic message template types, we just
      // display all available templates.
      $args=array();
      $args['conditions']['CoMessageTemplate.co_id']=$coid;
      $args['conditions']['CoMessageTemplate.status']=SuspendableStatusEnum::Active;
      $args['contain']=false;
      $coefs = $this->Yoda->CoMessageTemplate->find('all',$args);
      $selects=array();
      foreach($coefs as $ef)
      {
          $selects[$ef['CoMessageTemplate']['id']] = $ef['CoMessageTemplate']['description'];
      }
      $this->set('templates',$selects);

      $args=array();
      $args['conditions']['CoService.co_id']=$coid;
      $args['contain']=false;
      $cosvs = $this->Yoda->CoService->find('all',$args);
      $selects=array();
      foreach($cosvs as $service)
      {
          $selects[$service['CoService']['id']] = $service['CoService']['name'];
      }
      $this->set('services',$selects);

  }
}


