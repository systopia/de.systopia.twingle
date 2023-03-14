# Twingle API

Twingle is a payment service provider that makes it possible to create donation
forms with various payment options and embed them on websites or integrate them
into your homepage. Interested parties can donate via known payment options (
e.g. credit card, PayPal). The procedure is also set up and optimised for mobile
devices. If you want to use the Twingle fundraising service, you have to set up
a corresponding online account.

For further information about Twingle fundraising
see the [Twingle website](https://www.twingle.de/).

Twingle as fundraising service can be connected to CiviCRM via its API with the
extension **Twingle API**.

## Features

* Donations from Twingle can be automatically created as contributions in
  CiviCRM and assigned to existing or new contacts and administered in CiviCRM.
* Supporters and contacts of donations can be managed in CiviCRM.
* Donations can be submitted with different payment statuses depending on the
  payment type
* SEPA mandates can be created for one-off and recurring payments.
* Donors can be added into groups for receiving newsletters, mailings and
  donation receipts.
* A memberships can be set up for a donor.
* Data can be entered in user-defined fields

## Configuration

Following the successful installation of the Twingle API extension, there is
some configuration work to do in order to set up the smooth connection between
CiviCRM and Twingle fundraising service.

You have to carry out the following configuration steps:

* Creating a Twingle user and a user role in your CMS (Drupal, Wordpress, etc.)
* Configuring the *Extended Contact Matcher (XCM)* in CiviCRM
* Creating a Twingle User and an API key in CiviCRM
* Activating the SEPA connection in CiviCRM (optional)
* Configuring the Twingle profile in CiviCRM
* Configuring your Twingle Account on the Twingle website
