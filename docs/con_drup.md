# Twingle API: Creating a Twingle user and a user roll in your CMS system

After the installation of the Twingle API extension, various configuration steps must be carried out so that the connection functions smoothly. Among other things, certain configurations must be made on the CMS platform CiviCRM is implemented on. 

In an CMS system, administration of permissions is made easier by creating roles and assigning permissions to them rather than assigning permissions to each user.

The Twingle API requires a user with the name Twingle API and a user role with the name Twingle API in your CMS-system.

Here, the corresponding steps are described by way of example when using Drupal.

## New User Role in Drupal

1. In Drupal, go to **Administration/People/Permissions/Roles**.

2. Type Twingle API in the text box and select  **Add role**. To the right of your role there will be a *edit role* function and an *edit permissions* button. The *edit permissions* selection will show only the permission selections for the individual role.

3. As Permission you only have to select the following entry: **Twingle API: Access Twingle API**.

## New User Role in Wordpress

1. In CiviCRM, go to **Administer/User and Permissions (Access Control)**.

2. Then select the **WordPress Access Control** link. 
   Here you can adjust the CiviCRM settings for each of the predefined User Roles from WordPress.

3. Scroll down. As Permission you only have to select the following entry: **Twingle API: Access Twingle API**.
   

![](Img/Twin_per.png)

## New User in Drupal

1. In Drupal, go to **Administration/People**.

2. Then select **Add user**.

3. In user name field enter **Twingle API**

4. In the e-mail field enter either **info@example.de** or **mailtest@example.de**.

5. In Roles select **Twingle API**.
