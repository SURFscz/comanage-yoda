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
      $this->dbg('testing authorization');
    $roles = $this->Role->calculateCMRoles();
    
    $this->dbg('roles are '.json_encode($roles));

    $this->copersonid=null;
    if(isset($roles['copersonid']))
    {
        $this->copersonid = $roles['copersonid'];
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Kick of a new invite flow
    $p['invite'] = $roles['coadmin'];
    
    // Configure the page settings
    $p['config'] = $roles['coadmin'];

    // Delete (deactivate, deprovision) an existing user
    $p['delete'] = $roles['coadmin'];
    
    // Check activation state of a user
    $p['check'] = true;
    
    // Reset a Service token
    $p['reset'] = $roles['comember'];
    
    // See the index page
    $p['index'] = true;
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }

 /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v0.9.2
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = null) {
    if(in_array($this->action,array('index','config','reset','check'))) 
    {        
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      }
    }
    
    return parent::parseCOID();
  }

  public function index()
  {
      $this->dbg('YodaController::index');
      $coid = $this->cur_co['Co']['id'];

      $this->dbg('coid is '.$coid);

      $args=array();
      $args['conditions']['Yoda.co_id'] = $coid;
      $args['contain']=array('CoEnrollmentFlow','CoService');
      $yoda=$this->Yoda->find('first',$args);

      $this->dbg('Yoda is '.json_encode($yoda));

      $this->set('yoda',$yoda);

      if(empty($this->copersonid)) 
      {
          // do not support features for non-members of the current CO
          $this->Flash->set(_txt('er.permission'), array('key' => 'error'));
          $this->redirect("/");
      }
      $this->set('copersonid',$this->copersonid);
      
      if(!empty($yoda['CoService'])) 
      {
          // determine all available service tokens
          $this->loadModel('CoServiceToken');
          $args=array();
          $args['conditions']['CoServiceToken.co_person_id'] = $this->copersonid;
          $args['conditions']['CoServiceToken.co_service_id'] = $yoda['CoService']['id'];
          $args['contain']=false;

          $this->set('co_service_tokens',$this->CoServiceToken->find('all',$args));
          
          // load the relevant settings model
          $this->loadModel('CoServiceTokenSetting');
          $args=array();
          $args['conditions']['CoServiceTokenSetting.co_service_id'] = $yoda['CoService']['id'];
          $args['contain']=false;
          
          $this->set('co_service_token_setting',$this->CoServiceTokenSetting->find('first',$args));
      }

  }

  public function config()
  {
      $this->dbg('YodaController::config');
      $coid = $this->cur_co['Co']['id'];
      $this->dbg('current CO is '.$coid);
      
      $args=array();
      $args['conditions']['Yoda.co_id'] = $coid;
      $args['contain']=array('CoEnrollmentFlow','CoService');
      $yoda=$this->Yoda->find('first',$args);
      //$this->dbg('yoda is '.json_encode($yoda));

      $this->dbg(json_encode($this->request));
      if($this->request->is('post'))
      {
          $this->dbg('post request');
          try 
          {
              $data = $this->request->data;
              
              // link the Yoda and CO instance
              if(isset($yoda['Yoda']['id']))
              {
                  $data['Yoda']['id']=$yoda['Yoda']['id'];
              }
              
              $this->dbg('data is '.json_encode($data));
              $ret = $this->Yoda->save($data);
              if(!empty($ret))
              {
                  $yoda=array_merge($yoda,$ret);
              }
          }
          catch(Exception $e)
          {
              $err = filter_var($e->getMessage(),FILTER_SANITIZE_SPECIAL_CHARS);
              $this->dbg('caught error '.$err);
              $this->Flash->set($err ?: _txt('er.fields'), array('key' => 'error'));
          }
      }
      else 
      {
          $this->dbg('request is NOT a post');
      }

      // Set View variables

      $this->set('yoda',$yoda);

      $args=array();
      $args['conditions']['CoEnrollmentFlow.co_id']=$coid;
      $args['contain']=false;
      $coefs = $this->Yoda->Co->CoEnrollmentFlow->find('all',$args);
      $selects=array();
      foreach($coefs as $ef)
      {
          $selects[$ef['CoEnrollmentFlow']['id']] = $ef['CoEnrollmentFlow']['name'];
      }
      $this->set('efs',$selects);


      $args=array();
      $args['conditions']['CoService.co_id']=$coid;
      $args['contain']=false;
      $cosvs = $this->Yoda->Co->CoService->find('all',$args);
      $selects=array();
      foreach($cosvs as $service)
      {
          $selects[$service['CoService']['id']] = $service['CoService']['name'];
      }
      $this->set('services',$selects);

  }

  public function delete()
  {
  }

  public function check()
  {
    if($this->request->is('restful')) 
    {
    }
    else 
    {
        $this->Flash->set(_txt('er.permission'), array('key' => 'error'));
        $this->redirect("/");
    }
  }

  public function reset()
  {
    
  }
  
  private function dbg($txt)
  {
      CakeLog::write('debug',$txt);
  }
}


