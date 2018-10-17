# comanage-yoda
COmanage plugin for Yoda related sources

This plugin provides features specifically implemented for users of Yoda.

This project has the following deployment goals:
- create an Enrollment plugin that moves a fixed-attribute to an identifier of the newly created
  CoPerson
- allow setting preferred Yoda service and message template
- provide an API to regenerate a user service token


Use Case Description
====================
User wants to use the email address as identifying attribute. Using the FixedAttributeEnroller, the
email attribute can be checked against a preset value. Now the user wants to use this attribute as
an identifier (which is not supported in the IdentifierAssignment configurations of COmanage).

Furthermore, the user wants the newly enrolled and approved user to automatically receive an email 
with a link to generate a specific service token. COmanage does not allow plugins to interface in 
the template generation of the 'approved' message, so this plugin allows sending a different 
template and applies relevant replacements on that template.

This plugin extends the use of the FixedAttributeEnroller return URL parameters. Where the 
FixedAttributeEnroller distinguishes up to 2 fields for an attribute (attribute name and attribute type),
this plugin extends this to 3 fields, the last one being the identifier type. By default, this type is
'uid', but it can be set to any of the supported default identifier types,

A 'return' url parameter would then look something like:
https://your.domain.tld/your/path?attribute:type:idtype=sha256-hash

E.g.:
https://www.example.com/welcome?EmailAddress:preferred:uid=71e9ce9fd1485f1e79e8d966318cd1bb25472a00ab53f458a7a09fdd15d679d4

And the enrollment URL is then:
https://comanage.your.domain.tld/registry/co_petitions/start/coef:<id>/return=<base64 encoded url>

E.g.:

https://comanage.example.com/registry/co_petitions/start/coef:12/return=e9fd1485f1e79e8d966318cd1bb25472a00ab53f458a7a09fdd15d679d4"aHR0cHM6Ly93d3cuZXhhbXBsZS5jb20vd2VsY29tZT9FbWFpbEFkZHJlc3M6cHJlZmVycmVkOnVpZD03MWU5Y2U5ZmQxNDg1ZjFlNzllOGQ5NjYzMThjZDFiYjI1NDcyYTAwYWI1M2Y0NThhN2EwOWZkZDE1ZDY3OWQ0Cg==

Configuration
=============
Configuration can be accessed by CO administrators through the Configuration options page. However, the Yoda entry is
only displayed if a row exists for Yoda configuration in the database. To create this first row, manually visit the
configuration page at the following URL:

https://comanage.your.domain.tld/registry/yoda/yoda/index/co:<yoda-CO-id>

Configuration is only accessible to CoPerson that are member of CO:admins.

Reset
=====
The YodaController has a specific user feature to reset their service token, by accessing:

https://comanage.yourdomain.tld/registry/yoda/yoda/reset/co:<yoda-CO-id>

The currently logged in user is redirected to the CoServiceToken page that generates a new service token for the selected Yoda service.


Setup
=====
Checkout or link the plugin code to the `local/Plugin` directory of your COmanage Registry installation. Then 
update the app cache:

```
app/Console/cake database
```

The YodaEnroller is now run for every enrollment and configuration can be accessed.

Tests
=====
This plugin does not currently come with unit tests.


Disclaimer
==========
This plugin is provided AS-IS without any claims whatsoever to its functionality.
