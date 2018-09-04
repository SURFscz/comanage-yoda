<?php
    $model = $this->name;
    $req = Inflector::singularize($model);
    $submit_label = _txt('op.save');

    print $this->element("coCrumb");
    $args = array();
    $args['plugin'] = "yoda";
    $args['controller'] = 'yoda';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('ct.yoda'), $args);

    $this->Html->addCrumb(_txt('ct.yoda.config'));


    print $this->Form->create($req, array('inputDefaults' => array('label' => false, 'div' => false)));
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>

<ul id="<?php print $this->action; ?>_yoda_config" class="fields form-list form-list-admin">
 <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.yoda.enrollment'); ?></div>
      <div class="field-desc"><?php print _txt('fd.yoda.enrollment.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($yoda['Yoda']['co_enrollment_flow_id'])
                           ? $yoda['Yoda']['co_enrollment_flow_id']
                           : -1);
        $attrs['empty'] = false;
        
          print $this->Form->select('co_enrollment_flow_id',
                                    $efs,
                                    $attrs);
          
          if($this->Form->isFieldError('co_enrollment_flow_id')) {
            print $this->Form->error('co_enrollment_flow_id');
          }
      ?>
    </div>
  </li>

 <li>
    <div class="field-name">
      <div class="field-title"><?php print _txt('fd.yoda.service'); ?></div>
      <div class="field-desc"><?php print _txt('fd.yoda.service.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
        $attrs = array();
        $attrs['value'] = (isset($yoda['Yoda']['co_service_id'])
                           ? $yoda['Yoda']['co_service_id']
                           : -1);
        $attrs['empty'] = false;
        
          print $this->Form->select('co_service_id',
                                    $services,
                                    $attrs);
          
          if($this->Form->isFieldError('co_service_id')) {
            print $this->Form->error('co_service_id');
          }
      ?>
    </div>
  </li>

  <li class="fields-submit">
    <div class="field-name">
      <span class="required"><?php print _txt('fd.req'); ?></span>
    </div>
    <div class="field-info">
      <?php print $this->Form->submit($submit_label); ?>
    </div>
  </li>
</ul>
<?php
  print $this->Form->end();
?>
