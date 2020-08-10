# Twingle API

Extension to connect to the Twingle fundraising service via its API.

* [About Twingle](https://www.twingle.de/)

The extension is licensed under
[AGPL-3.0](https://github.com/systopia/de.systopia.twingle/blob/master/LICENSE.txt).

## Configuration

### Configure Twingle

Please refer to the
[Twingle FAQ on using Twingle with CiviCRM](https://support.twingle.de/faq/de-de/9/46)
(currently only available in German).

### Configure Extended Contact Matcher (XCM)

Make sure you use an XCM profile with the option *Match contacts by contact ID*
enabled.

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

| Label                                 | Description                                                                                                                                                                              |
|---------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Profile name                          | Internal name, used inside the extension.                                                                                                                                                |
| Project IDs                           | Twingle project IDs. Separate multiple IDs with commas.                                                                                                                                  |
| Location type                         | Specify how the address data sent by the form should be categorised in CiviCRM. The list is based on your CiviCRM configuration.                                                         |
| Location type for organisations       | Specify how the address data sent by the form should be categorised in CiviCRM for organisational donations. The list is based on your CiviCRM configuration.                            |
| Financial type                        | Specify which financial type incoming one-time donations should be recorded with in CiviCRM. The list is based on your CiviCRM configuration.                                            |
| Financial type (recurring)            | Specify which financial type incoming recurring donations should be recorded with in CiviCRM. The list is based on your CiviCRM configuration.                                           |
| CiviSEPA creditor                     | When enabled to integrate with CiviSEPA, specify the CiviSEPA creditor to use.                                                                                                           |
| Gender options                        | Specify which CiviCRM gender option the incoming Twingle gender value should be mapped to. The list is based on your CiviCRM configuration.                                              |
| Record *Payment method* as            | Specifiy the payment methods mapping for incoming donations for each Twingle payment method.                                                                                             |
| Double-Opt-In                         | Group membership for newsletter mailing lists will be pending until receivement of confirming API call. Usage in combination with activated Double-Opt-In in Twingle manager.            |
| Sign up for groups                    | Whenever the donor checked the newsletter/postal mailing/donation receipt checkbox on the Twingle form, the contact will be added to the groups listed here.                             |
| Assign donation to campaign           | The donation will be assigned to the selected campaign. If a campaign ID is being submitted using the `campaign_id` parameter, this setting will be overridden with the submitted value. |
| Create membership of type             | A membership of the selected type will be created for the Individual contact for incoming one-time donations. If no membership type is selected, no membership will be created.          |
| Create membership of type (recurring) | A membership of the selected type will be created for the Individual contact for incoming recurring donations. If no membership type is selected, no membership will be created.         |
| Contribution source                   | The configured value will be set as the "Source" field for the contribution.                                                                                                             |
| Custom field mapping                  | Additional field values may be set to CiviCRM custom fields using a mapping. See the option's help text for the exact format.                                                            |


## API documentation

The extension provides a new CiviCRM API entity `TwingleDonation` with API
actions to record a new donation, end a previously submitted recurring donation
and cancel previously submitted donation.

### Submit donation

This API action processes submitted Twingle donations and donor information.

- Entity: `TwingleDonation`
- Action: `Submit`

The action accepts the following parameters:

| Parameter                              | Type    | Description                                                                         | Values/Format                                                                                                                                                                                                                                                                                                                                                                                                                 | Required                                                        |
|----------------------------------------|---------|-------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------|
| <nobr>`project_id`</nobr>              | String  | The Twingle project ID                                                              |                                                                                                                                                                                                                                                                                                                                                                                                                               | Yes                                                             |
| <nobr>`trx_id`</nobr>                  | String  | The unique transaction ID of the donation                                           | A unique transaction ID for the donation.                                                                                                                                                                                                                                                                                                                                                                                     | Yes                                                             |
| <nobr>`confirmed_at`</nobr>            | String  | The date when the donation was issued                                               | A string representing a date in the format `YmdHis`                                                                                                                                                                                                                                                                                                                                                                           | Yes                                                             |
| <nobr>`purpose`</nobr>                 | String  | The purpose of the donation                                                         |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`amount`</nobr>                  | Integer | The donation amount in minor currency unit                                          |                                                                                                                                                                                                                                                                                                                                                                                                                               | Yes                                                             |
| <nobr>`currency`</nobr>                | String  | The ISO-4217 currency code of the donation                                          | A valid ISO-4217 currency code                                                                                                                                                                                                                                                                                                                                                                                                | Yes                                                             |
| <nobr>`newsletter`</nobr>              | Boolean | Whether to subscribe the contact to the newsletter group defined in the profile     |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`postinfo`</nobr>                | Boolean | Whether to subscribe the contact to the postal mailing group defined in the profile |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`donation_receipt`</nobr>        | Boolean | Whether the contact requested a donation receipt                                    |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`payment_method`</nobr>          | String  | The Twingle payment method used for the donation                                    | One of:<br /><ul><li><nobr>`banktransfer`</nobr></li><li><nobr>`debit_manual`</nobr></li><li><nobr>`debit_automatic`</nobr></li><li><nobr>`creditcard`</nobr></li><li><nobr>`mobilephone_germany`</nobr></li><li><nobr>`paypal`</nobr></li><li><nobr>`sofortueberweisung`</nobr></li><li><nobr>`amazonpay`</nobr></li><li><nobr>`paydirekt`</nobr></li><li><nobr>`applepay`</nobr></li><li><nobr>`googlepay`</nobr></li></ul> | Yes                                                             |
| <nobr>`donation_rhythm`</nobr>         | String  | The interval which the donation is recurring in                                     | One of:<br /><ul><li><nobr>`'one_time',`</nobr></li><li><nobr>`'halfyearly',`</nobr></li><li><nobr>`'quarterly',`</nobr></li><li><nobr>`'yearly',`</nobr></li><li><nobr>`'monthly'`</nobr></li></ul>                                                                                                                                                                                                                          | Yes                                                             |
| <nobr>`debit_iban`</nobr>              | String  | The IBAN for SEPA Direct Debit payments                                             | A valid ISO 13616-1:2007 IBAN                                                                                                                                                                                                                                                                                                                                                                                                 | Yes, if `payment_method` is `debit_manual` and CiviSEPA is used |
| <nobr>`debit_bic`</nobr>               | String  | The BIC for SEPA Direct Debit payments                                              | A valid ISO 9362 BIC                                                                                                                                                                                                                                                                                                                                                                                                          | Yes, if `payment_method` is `debit_manual` and CiviSEPA is used |
| <nobr>`debit_mandate_reference`</nobr> | String  | The mandate reference for SEPA Direct Debit payments                                |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`debit_account_holder`</nobr>    | String  | The account holder for SEPA Direct Debit payments                                   |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`is_anonymous`</nobr>            | Boolean | Whether the donation is submitted anonymously                                       |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_gender`</nobr>             | String  | The gender of the contact                                                           |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_birthdate`</nobr>          | String  | The date of birth of the contact                                                    | A string representing a date in the format `Ymd`                                                                                                                                                                                                                                                                                                                                                                              |                                                                 |
| <nobr>`user_title`</nobr>              | String  | The formal title of the contact                                                     |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_email`</nobr>              | String  | The e-mail address of the contact                                                   | A valid e-mail address                                                                                                                                                                                                                                                                                                                                                                                                        |                                                                 |
| <nobr>`user_firstname`</nobr>          | String  | The first name of the contact                                                       |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_lastname`</nobr>           | String  | The last name of the contact                                                        |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_street`</nobr>             | String  | The street address of the contact                                                   |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_postal_code`</nobr>        | String  | The postal code of the contact                                                      |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_city`</nobr>               | String  | The city of the contact                                                             |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_country`</nobr>            | String  | The country of the contact                                                          | [ISO 3166-1 Alpha-2 country codes](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements)                                                                                                                                                                                                                                                                                                        |                                                                 |
| <nobr>`user_telephone`</nobr>          | String  | The telephone number of the contact                                                 |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_company`</nobr>            | String  | The company of the contact                                                          |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`user_extrafield`</nobr>         | String  | Additional information of the contact                                               |                                                                                                                                                                                                                                                                                                                                                                                                                               |                                                                 |
| <nobr>`campaign_id`</nobr>             | Integer | The CiviCRM ID of a campaign to assign the contribution                             | A valid CiviCRM Campaign ID. This overrides the campaign ID configured within the profile.                                                                                                                                                                                                                                                                                                                                    |                                                                 |

You may also refer to
[the code](https://github.com/systopia/de.systopia.twingle/blob/master/api/v3/TwingleDonation/Submit.php)
for more insight into this API action.

### End recurring donation

- Entity: `TwingleDonation`
- Action: `Endrecurring`

The action accepts the following parameters:

| Parameter                 | Type    | Description                                    | Values/Format                                         | Required |
|---------------------------|---------|------------------------------------------------|-------------------------------------------------------|----------|
| <nobr>`project_id`</nobr> | String  | The Twingle project ID                         |                                                       | Yes      |
| <nobr>`trx_id`</nobr>     | String  | The unique transaction ID of the donation      | A unique transaction ID for the donation.             | Yes      |
| <nobr>`ended_at`</nobr>   | Integer | The date when the recurring donation was ended | A string representing a date in the format `YmdHis`   | Yes      |

You may also refer to
[the code](https://github.com/systopia/de.systopia.twingle/blob/master/api/v3/TwingleDonation/Endrecurring.php)
for more insight into this API action.

### Cancel donation

- Entity: `TwingleDonation`
- Action: `Cancel`

The action accepts the following parameters:

| Parameter                    | Type   | Description                                        | Values/Format                                         | Required |
|------------------------------|--------|----------------------------------------------------|-------------------------------------------------------|----------|
| <nobr>`project_id`</nobr>    | String | The Twingle project ID                             |                                                       | Yes      |
| <nobr>`trx_id`</nobr>        | String | The unique transaction ID of the donation          | A unique transaction ID for the donation.             | Yes      |
| <nobr>`cancelled_at`</nobr>  | String | The date when the recurring donation was cancelled | A string representing a date in the format `YmdHis`   | Yes      |
| <nobr>`cancel_reason`</nobr> | String | The reason for the donation being cancelled         |                                                       | Yes      |

You may also refer to
[the code](https://github.com/systopia/de.systopia.twingle/blob/master/api/v3/TwingleDonation/Cancel.php)
for more insight into this API action.

### Double-Opt-In confirmation

- Entity: `TwingleDonation`
- Action: `doubleoptinconfirm`

The action accepts the following parameters:

| Parameter                    | Type   | Description                                        | Values/Format                                         | Required |
|------------------------------|--------|----------------------------------------------------|-------------------------------------------------------|----------|
| <nobr>`project_id`</nobr>    | String | The Twingle project ID                             |                                                       | Yes      |
| <nobr>`user_email`</nobr>    | String | The e-mail address of the contact                  | A valid e-mail address                                | Yes      |

You may also refer to
[the code](https://github.com/systopia/de.systopia.twingle/blob/master/api/v3/TwingleDonation/Doubleoptinconfirm.php)
for more insight into this API action.