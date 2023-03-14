# Twingle account settings on the Twingle website

The use of the Twingle API extension requires that you already have a Twingle
account and that you make some settings in this account for the connection.

You will have to provide the following settings in your Twingle account settings
in order to send donations to CiviCRM:

1. API key from your Twingle user
2. Site key
3. URL

Important: The URL must always be the complete URL to the CiviCRM REST API
endpoint.
Examples:

- Drupal (with the *AuthX* extension): https://example.org/civicrm/ajax/rest
- Drupal (legacy
  method): https://example.org/sites/all/modules/civicrm/extern/rest.php
- Wordpress (with CiviCRM <
  5.25): https://example.org/wp-content/plugins/civicrm/civicrm/extern/rest.php
- Wordpress (with CiviCRM
  5.25+): https://example.org/wp-json/civicrm/v3/rest

For detailled information, please see
the [Twingle documentation](https://support.twingle.de/faq/de-de/9-anbindung-externer-systeme/46-wie-kann-ich-civicrm-mit-twingle-nutzen) (
in German language only).
