# Configuring the Extended Contact Manager extension (XCM)

After the installation of the Twingle API extension, various configuration steps
must be carried out so that the connection functions smoothly. Twingle API
depends on the *Extended Contact Manager (XCM)* extension.

Taking over contact data using the Twingle API means that they may produce
duplicates in your CiviCRM contact management. Before contacts are added or
updated in CiviCRM a data check should take place to avoid this problem. This
data check is handled by the *Extended Contact Manager (XCM)* extension. This
extension must be configured accordingly for use with Twingle by defining a
corresponding profile.

## Creating an XCM Profile

Your first task regarding the Extended Contact Manager extension (XCM)
configuration will be to create an XCM profile to be used for the Twingle API.
This works best if you copy the *Default* profile.

1. In CiviCRM, go to **Administer**.

2. Select **Xtended Contact Matcher (XCM) Configuration** in the **System
   Settings** section.

![](../img/XCMAdmin.jpg)

3. Click on **Copy** in the **Default** profile.

4. Rename the new profile with **Twingle** in the **Profile name** field.

![](../img/ProNam.jpg)

5. Click **Save** at the bottom of this window. In the Profiles overview you can
   find your new Twingle profile.

![](../img/XCM_Profile.jpg)

## Set up the Extended Contact Manager extension

After you have created the XCM profile, you must enter the configuration
settings for the Twingle connection to CiviCRM in this profile. Generally, you
will find a description of all the settings in
the [Extended Contact Manager (XCM) documentation](https://docs.civicrm.org/xcm/en/latest/configuration/).

Here you will find as support screenshots of the various sections of the
Extended Contact Manager extension (XCM). The settings are only an example.
Please adapt the settings to your individual requirements or environnement.

#### General section

![](../img/XCMGen.jpg)

#### Update section

![](../img/XCMUpda.jpg)

#### Assignment rules section

![](../img/XCMReg.jpg)

#### Identified contacts section

![](../img/XCMIde.jpg)

#### New contact section

![](../img/XCMNeu.jpg)

#### Duplicate section

![](../img/XCMDup.jpg)

#### Difference Handling section

![](../img/xcmdif.jpg)
