# Twingle API

Extension to connect to the Twingle fundraising service via its API.

* [About Twingle](https://www.twingle.de/)

The extension is licensed under
[AGPL-3.0](https://github.com/systopia/de.systopia.twingle/blob/master/LICENSE.txt).

## Configuration

### Configure Twingle

*This section is yet to be completed.*

### Configure CiviCRM

- Go to the Administration console `/civicrm/admin`
- Open "Twingle API Configuration" at `/civicrm/admin/settings/twingle`

#### Configure CiviSEPA integration

Open "Configure extension settings" at
`/civicrm/admin/settings/twingle/settings` and configure whether to integrate
with the [CiviSEPA](https://github.com/project60/org.project60.sepa) extension.

This enables you to map incoming donations from Twingle with a specific payment
method (e.g. *debit_manual*) to be processed with CiviSEPA, that is, creating a
SEPA mandate and managing recurring payments.

#### Configure profiles

Open "Configure profiles" at `/civicrm/admin/settings/twingle/profiles`.

The *default* profile is used whenever the plugin cannot match the Twingle
project ID from any other profile. Therefore the default profile will be used
for all newly created Twingle projects.

| Label                      | Description                                                                                                                                                  |
|----------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Profile name               | Internal name, used inside the extension.                                                                                                                    |
| Project IDs                | Twingle project IDs. Separate multiple IDs with commas.                                                                                                      |
| Location type              | Specify how the address data sent by the form should be categorised in CiviCRM. The list is based on your CiviCRM configuration.                             |
| Financial type             | Specify which financial type incoming donations should be recorded with in CiviCRM. The list is based on your CiviCRM configuration.                         |
| Gender options             | Specify which CiviCRM gender option the incoming Twingle gender value should be mapped to. The list is based on your CiviCRM configuration.                  |
| Record *Payment method* as | Specifiy the payment methods mapping for incoming donations for each Twingle payment method.                                                                 |
| CiviSEPA creditor          | When enabled to integrate with CiviSEPA, specify the CiviSEPA creditor to use.                                                                               |
| Sign up for groups         | Whenever the donor checked the newsletter/postal mailing/donation receipt checkbox on the Twingle form, the contact will be added to the groups listed here. |


## API documentation

The extension provides a new CiviCRM API entity `TwingleDonation` with API
actions to record a new donation, end a previously submitted recurring donation
and cancel previously submitted donation.

### Submit donation

- Entity: `TwingleDonation`
- Action: `Submit`

*This section is to be completed: Add parameters documentation and describe what
the action does. In the meantime, refer to
[the code](https://github.com/systopia/de.systopia.twingle/blob/master/api/v3/TwingleDonation/Submit.php)*

### End recurring donation

- Entity: `TwingleDonation`
- Action: `Endrecurring`

*This section is to be completed: Add parameters documentation and describe what
the action does. In the meantime, refer to
[the code](https://github.com/systopia/de.systopia.twingle/blob/master/api/v3/TwingleDonation/Endrecurring.php)*

### Cancel donation

- Entity: `TwingleDonation`
- Action: `Cancel`

*This section is to be completed: Add parameters documentation and describe what
the action does. In the meantime, refer to
[the code](https://github.com/systopia/de.systopia.twingle/blob/master/api/v3/TwingleDonation/Cancel.php)*
