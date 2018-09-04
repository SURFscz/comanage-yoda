<?php
    print $this->element("coCrumb");
    $this->Html->addCrumb(_txt('ct.yoda'));

    // Determine which services have tokens set
    $tokensSet=array();
    if(!empty($co_service_tokens)) 
    {
        $tokensSet = Hash::extract($co_service_tokens, '{n}.CoServiceToken.co_service_id');
    }

?>
<div id="co_enrollment_flows" class="co-grid co-grid-with-header mdl-shadow--2dp">
  <div class="mdl-grid co-grid-header">
    <div class="mdl-cell mdl-cell--9-col"><?php print _txt('fd.name'); ?></div>
    <div class="mdl-cell mdl-cell--2-col actions"><?php print _txt('fd.actions'); ?></div>
  </div>

<?php
    // Display the kick-off enrollment flow button for any user
    if($permissions['invite'] && isset($yoda['CoEnrollmentFlow'])) 
    {
?>
  <div class="mdl-grid">
    <div class="mdl-cell mdl-cell--9-col mdl-cell--6-col-tablet mdl-cell--2-col-phone first-cell">
      <?php print _txt('fd.yoda.enroll'); ?>
      <div class="field-desc">
        <?php print filter_var($yoda['CoEnrollmentFlow']['name'],FILTER_SANITIZE_SPECIAL_CHARS); ?>
      </div>
    </div>
    <div class="mdl-cell mdl-cell--2-col actions">
      <?php
          // begin button
          print $this->Html->link(_txt('op.begin') . ' <em class="material-icons" aria-hidden="true">forward</em>',
            array(
              'controller' => 'co_petitions',
              'action' => 'start',
              'coef' => $yoda['CoEnrollmentFlow']['id']
            ),
            array(
              'class' => 'co-button mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect',
              'escape' => false
            )
          ) . "\n";

          // QR code button - requires GD2 library
          if (extension_loaded ("gd")) {
            print $this->Html->link(
              $this->Html->image(
                'qrcode-icon.png',
                array(
                  'alt' => _txt('op.display.qr.for',array(filter_var($yoda['CoEnrollmentFlow']['name'],FILTER_SANITIZE_SPECIAL_CHARS)))
                )
              ),
              array(
                'controller' => 'qrcode',
                '?' => array(
                  'c' => $this->Html->url(
                    array(
                      'controller' => 'co_petitions',
                      'action' => 'start',
                      'coef' => $yoda['CoEnrollmentFlow']['id']
                    ),
                    array(
                      'full' => true,
                      'escape' => false
                    )
                  )
                )
              ),
              array(
                'class' => 'co-button qr-button mdl-button mdl-js-button mdl-button--raised mdl-button--colored mdl-js-ripple-effect', 
                'escape' => false,
                'title'  => _txt('op.display.qr.for',array($yoda['CoEnrollmentFlow']['name']))
              )
            ) . "\n";
          }
      ?>
    </div>
  </div>
<?php
    } // if permission to invite
?>


<?php
    if(!empty($yoda['CoService'])) {
        ?>
  <div class="mdl-grid">
    <div class="mdl-cell mdl-cell--9-col mdl-cell--6-col-tablet mdl-cell--2-col-phone first-cell">
      <?php print _txt('fd.yoda.reset_token'); ?>
    </div>
    <div class="mdl-cell mdl-cell--2-col actions">
        <?php
          // Link to generate a new token
          
          $txtkey = "";
          
          if(in_array($yoda['CoService']['id'], $tokensSet)) {
            // Token exists
            $txtkey = 'pl.coservicetoken.confirm.replace';
          } else {
            $txtkey = 'pl.coservicetoken.confirm';
          }
            
          print '<button type="button" class="provisionbutton" title="' . _txt('pl.coservicetoken.generate')
                . '" onclick="javascript:js_confirm_generic(\''
                . _txt($txtkey, array(filter_var(_jtxt("Yoda"),FILTER_SANITIZE_STRING))) . '\',\''    // dialog body text
                . $this->Html->url(              // dialog confirm URL
                    array(
                      'plugin'       => 'co_service_token',
                      'controller'   => 'co_service_tokens',
                      'action'       => 'generate',
                      'tokensetting' => $co_service_token_setting['CoServiceTokenSetting']['id'],
                      'copersonid'   => $copersonid
                    )
                  ) . '\',\''
                . _txt('pl.coservicetoken.generate') . '\',\''    // dialog confirm button
                . _txt('op.cancel') . '\',\''    // dialog cancel button
                . _txt('pl.coservicetoken.generate') . '\',[\''   // dialog title
                . ''  // dialog body text replacement strings
                . '\']);">'
                . _txt('pl.coservicetoken.generate')
                . '</button>';
        ?>
    </div>
  </div>
<?php 
    } // if service set
?>

  <div class="clearfix"></div>
</div>
<?php 
    
    if($permissions['config'])
    {
?>
<ul id="configuration-menu" class="three-col">
  <li>
    <?php
        $args = array();
        $args['plugin'] = "yoda";
        $args['controller'] = 'yoda';
        $args['action'] = 'config';
        $args['co'] = $cur_co['Co']['id'];

        print $this->Html->link(_txt('ct.yoda.config'), $args);
?>
   </li>
</ul>
<?php 
    } // if config permissions
?>
