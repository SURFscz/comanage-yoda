<?php
/**
 * COmanage Registry Yoda Model
 *
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
class Yoda extends AppModel {
  // Define class name for cake
  public $name = "Yoda";
  
  public $useTable="yoda";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $hasOne = array();
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "CoEnrollmentFlow",
    "CoService"
  );

  // Default display field for cake generated views
  public $displayField = "Yoda";
  
  // Default ordering for find operations
  public $order = array("Yoda.id");
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    )
  );
  
  // Enum type hints
  
  public $cm_enum_types = array(
    'status' => 'SuspendableStatusEnum'
  );
  
  /**
   * Callback after model save.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Boolean $created True if new model is saved (ie: add)
   * @param  Array $options Options, as based to model::save()
   * @return Boolean True on success
   */
  
  public function afterSave($created, $options = Array()) {
    if($created && !empty($this->data['Co']['id'])) {
      // Run setup for new CO
      
      $this->setup($this->data['Co']['id']);
    }
    
    return true;
  }
  
  /**
   * Perform initial setup for a CO.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer CO ID
   * @return Boolean True on success
   */
  
  public function setup($coId) {
    // Set up the default values for extended types
    $this->CoExtendedType->addDefaults($coId);
    
    // Create the default groups
    $this->CoGroup->addDefaults($coId);
    
    return true;
  }
}
