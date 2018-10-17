<?php
/**
 * ServiceTokenMailerComponent
 *
 * Author licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link          http://www.surfnet.nl
 * @package       COmanage-yoda
 * @since         2018-10-01
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 *
 * This component encapsulates the work required to create a new service token and 
 * render an email to be sent to the given CoPerson
 */

class ServiceTokenMailerComponent extends Component {

  /**
   * Initialize
   *
   * @param Object $controller Controller
   */
  public function initialize($controller) {
    $this->controller=$controller;
  }

  /**
   * Retrieve the ServiceToken generate URL
   *
   * @param Array $coperson CoPerson artifact
   * @return string service token content
   */
  public function createTokenUrl($coperson) {

    $url = "";

    if(!empty($this->yoda['CoServiceTokenSetting'])) {
      // https://comanage.scz-vm.net/registry/co_service_token/co_service_tokens/generate/tokensetting:2/copersonid:110
      $url = array(
          "plugin" => "co_service_token",
          "controller" => "co_service_tokens",
          "action" => "generate",
          "tokensetting" => $this->yoda['CoServiceTokenSetting']['id'],
          "copersonid" => $coperson['id'],
      );
      $url = Router::url($url, true);
    }

    return $url;
  }

  /**
   * Find an eligible email address of the CoPerson
   *
   * @param Array $coperson CoPerson artifact
   * @return Array email address artifact
   */
  public function findEmailAddress($coperson) {
    $emailModel = ClassRegistry::init('EmailAddress');

    $args=array();
    $args['conditions']['EmailAddress.co_person_id'] = $coperson['id'];
    $args['contain']=false;
    $eas = $emailModel->find('all',$args);

    $preferred=null;
    $official=null;
    $any=null;
    foreach($eas as $ea) {
      if($ea['EmailAddress']['type'] == EmailAddressEnum::Preferred) {
        return $ea['EmailAddress'];
      } else if ($ea['EmailAddress']['type'] == EmailAddressEnum::Official) {
        $official = $ea['EmailAddress'];
      }
      $any = $ea['EmailAddress'];
    }

    return $official === null ? $any : $official;
  }

  public function getYodaConfig($coid) {
    // CoServiceTokenSetting is not bound to CoService and apparently we
    // cannot bind it live, as is done in the CoServiceToken controller
    $yodaModel = ClassRegistry::init('Yoda.Yoda');
    $args=array();
    $args['conditions']['Yoda.co_id'] = $coid;
    $args['contain']=array('CoMessageTemplate', 'CoService');
    $this->yoda=$yodaModel->find('first',$args);

    if(!empty($this->yoda) && isset($this->yoda['CoService'])) {
      $serviceTokenSettingModel = ClassRegistry::init('CoServiceToken.CoServiceTokenSetting');
      $args=array();
      $args['conditions']['CoServiceTokenSetting.co_service_id']=$this->yoda['CoService']['id'];
      $args['contain']=false;
      $this->yoda += $serviceTokenSettingModel->find('first',$args);
    }
    return $this->yoda;
  }

  /**
   * Send an email using the specified template to the provided user
   *
   * @param Array $coperson CoPerson artifact
   * @return none
   */
  public function sendNewToken($copersonid, $is_enrolling=true) {
    $coPersonModel = ClassRegistry::init('CoPerson');
    $args=array();
    $args['conditions']['CoPerson.id']=$copersonid;
    $args['contain']=array('PrimaryName', 'Identifier','Co');
    $coperson=$coPersonModel->find('first',$args);

    $ea = $this->findEmailAddress($coperson['CoPerson']);
    if(!empty($ea)) {
      $this->getYodaConfig($coperson['CoPerson']['co_id']);
      $template = isset($this->yoda['CoMessageTemplate']) ? $this->yoda['CoMessageTemplate'] : null;

      if(empty($template)) {
        $template = array(
          "message_subject" => _txt("pl.yoda.default_token_subject"),
          "message_body" => _txt("pl.yoda.default_token_template"),
          "cc" => null,
          "bcc" => null
        );
      }

      $subs = array(
        'CO_PERSON' => generateCn($coperson['PrimaryName']),
        'TOKEN' => $this->createTokenUrl($coperson['CoPerson']),
        'CO_NAME' => $coperson['Co']['name']
      );

      $subject = $template['message_subject'];
      $body = $template['message_body'];
      $cc = $template['cc'];
      $bcc = $template['bcc'];

      $subject = processTemplate($subject, $subs, $coperson['Identifier']);
      $body = processTemplate($body, $subs, $coperson['Identifier']);
      $comment = _txt('pl.yoda.password_comment' . ($is_enrolling ? "_enroll" : "_request"));

      $src = array();
      $src['controller'] = 'co_service_token/co_service_tokens';
      $src['action'] = 'index/copersonid:'.$coperson['CoPerson']['id'];

      $coPersonModel->CoNotificationRecipient->register($coperson['CoPerson']['id'],
                null,
                $this->controller->Session->read('Auth.User.co_person_id'),
                'coperson',
                $coperson['CoPerson']['id'],
                'XTOK', // re-use XTOK assignment from ServiceToken plugin
                $comment,
                $src,
                false, // must-resolve
                null, // use default from address
                $subject,
                $body,
                $cc,
                $bcc);
    } // if not empty email
  }
}
