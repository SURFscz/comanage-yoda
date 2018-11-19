<?php

/* Author licenses this file to you under the Apache License, Version 2.0
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
 *
 * Plugin used to move matched attributes to a newly created identifier.
 * This plugin also looks for the Yoda plugin and tries to retrieve template and service information
 * to send a new service token by email to the newly approved user.
 *
 * Author: Michiel Uitdehaag, 2018, michiel.uitdehaag@surfnet.nl
 */

App::uses('CoPetitionsController', 'Controller');

class YodaCoPetitionsController extends CoPetitionsController {
  // Class name, used by Cake
  public $name = "YodaCoPetitions";
  public $components=array('FixedAttributeEnroller.FixedAttribute', 'Yoda.ServiceTokenMailer');

  /**
   * Plugin functionality following finalize step
   *
   * Create identifiers of type 'uid' or a specifically specified type. Only do this for non-identifiers.
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
  protected function execute_plugin_finalize($id, $onFinish) {

    // Get the Petition artifact
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain'] = array('EnrolleeOrgIdentity' => array(
      'Address',
      'EmailAddress',
      'Identifier',
      'Name',
      'PrimaryName' => array('conditions' => array('PrimaryName.primary_name' => true)),
      'TelephoneNumber',
      'Url'
     )
    );

    $copetition = $this->CoPetition->find('first', $args);

    // re-validate the passed parameters just to be sure the user was not meddling with
    // the actual XHR calls and has skipped the FixedAttributeEnroller
    if(!$this->FixedAttribute->checkAttributes($copetition)) {
      // we can safely give an ugly error now
      throw new RuntimeException("Not Authorized");
    }

    try {
      $values = $this->FixedAttribute->parseUrl($copetition['CoPetition']['return_url']);

      if(sizeof($values) && is_array($values)) {
        foreach($values as $key=>$value) {

          $valuefound = $this->FixedAttribute->getAttribute($copetition['EnrolleeOrgIdentity'], $key);
          if($valuefound !== null) {
            // valuefound is a collection of all attributes values that match the search
            // Take the first value
            if(is_array($valuefound)) {
              $valuefound=$valuefound[0];
            }
            $attribute = $key;
            $values = explode(':',$key);
            $type=IdentifierEnum::UID;
            if(sizeof($values) > 2) {
              $attribute=$values[0];
              $type=$values[2];

              // use $this->CoPetition->EnrolleeOrgIdentity->Identifier->validate['type']['content']['rule'][1][['default']
              // or use a fixed array...
              // We could argue that the allowed list of types is governed by the API of this plugin and not directly by the
              // list of supported types of COmanage...
              if(!in_array($type, array(IdentifierEnum::Badge,
                                        IdentifierEnum::Enterprise,
                                        IdentifierEnum::ePPN,
                                        IdentifierEnum::ePTID,
                                        IdentifierEnum::ePUID,
                                        IdentifierEnum::Mail,
                                        IdentifierEnum::National,
                                        IdentifierEnum::Network,
                                        IdentifierEnum::OpenID,
                                        IdentifierEnum::ORCID,
                                        IdentifierEnum::ProvisioningTarget,
                                        IdentifierEnum::Reference,
                                        IdentifierEnum::SORID,
                                        IdentifierEnum::UID)))
              {
                $type = IdentifierEnum::UID;
              }
            }

            if($attribute != 'Identifier') {
              $this->loadModel('Identifier');

              $args = array();
              $args['conditions']['identifier'] = $valuefound;
              $args['conditions']['type'] = $type;
              $args['conditions']['co_person_id'] = $copetition['CoPetition']['enrollee_co_person_id'];
              $args['contain'] = false;
              $uid = $this->Identifier->find('first', $args);

              if (empty($uid)) {
                $identifierData = array();
                $identifierData['Identifier']['identifier'] = $valuefound;
                $identifierData['Identifier']['type'] = $type;
                $identifierData['Identifier']['login'] = false;
                $identifierData['Identifier']['status'] = StatusEnum::Active;
                $identifierData['Identifier']['co_person_id'] = $copetition['CoPetition']['enrollee_co_person_id'];

                $this->Identifier->create($identifierData);
                $this->Identifier->save($identifierData, array('provision' => false));
              }
              // else the identifier already exists. Accept this and go on. Something else might fail
              // later on (when identifiers are checked at the SP), or it might not. 
            } // else this is an identifier attribute, do not copy it
          } // else this is an empty value. We should have bailed on this early on, do not bail now
        }
      } // else we did not find any return url parameters, this plugin is not used
    }
    catch(Exception $e) {
      // ignore any errors, this is not an essential step
    }

    $this->redirect($onFinish);
  }

  /**
   * Plugin functionality following provisioning step
   *
   * At this point, all the required data is present and we have succesfully provisioned the
   * user to external systems. We can now automatically send the new service token to the user
   *
   * @param Integer $id CO Petition ID
   * @param Array $onFinish URL, in Cake format
   */
  protected function execute_plugin_provision($id, $onFinish) {
    // Get the Petition artifact
    $args = array();
    $args['conditions']['CoPetition.id'] = $id;
    $args['contain'] = array('EnrolleeCoPerson',
      'EnrolleeOrgIdentity' => array(
        'Address',
        'EmailAddress',
        'Identifier',
        'Name',
        'PrimaryName' => array('conditions' => array('PrimaryName.primary_name' => true)),
        'TelephoneNumber',
        'Url'
       )
      );
    $copetition=$this->CoPetition->find('first',$args);

    // re-validate the passed parameters just to be sure the user was not meddling with
    // the actual XHR calls and has skipped the FixedAttributeEnroller
    if(!$this->FixedAttribute->checkAttributes($copetition)) {
      // we can safely give an ugly error now
      throw new RuntimeException("Not Authorized");
    }

    $this->ServiceTokenMailer->sendNewToken($copetition['EnrolleeCoPerson']['id'], true);

    $this->redirect($onFinish);
  }
}
