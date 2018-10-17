<?php
/**
 * COmanage Registry Yoda Plugin Language File
 *
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_yoda_texts['en_US'] = array(
  // Titles, per-controller
  'pl.ct.yoda' => 'Yoda',
  'pl.fd.yoda.enroll' => 'Start enrollment',
  'pl.fd.yoda.reset_token' => 'Reset service token',
  'pl.fd.yoda.template'  => 'Email message template',
  'pl.fd.yoda.template.desc'  => 'Select the template to be used to inform users of a new service token',
  'pl.fd.yoda.service'  => 'Service',
  'pl.fd.yoda.service.desc'  => 'Select the Yoda service entry for service token generation and adjustment',
  'pl.yoda.default_token_subject' => 'New password for Yoda',
  'pl.yoda.default_token_template' => 'Dear (@CO_PERSON),\r\n\r\nPlease find enclosed the new token password required for access to the Yoda server:\r\n(@TOKEN)\r\n\r\nRegards,\r\n\r\nSystem Administration\r\n',
  'pl.yoda.password_comment_enroll' => "A new service token password was sent to the email address for this CoPerson after completing enrollment",
  'pl.yoda.password_comment_request' => "A new service token password was sent to the email address for this CoPerson upon request",
);

