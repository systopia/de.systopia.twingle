<?php
/*------------------------------------------------------------+
| SYSTOPIA Twingle Integration                                |
| Copyright (C) 2018 SYSTOPIA                                 |
| Author: J. Schuppe (schuppe@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

use CRM_Twingle_ExtensionUtil as E;

/**
 * TwingleDonation.DoubleOptInConfirm API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $params description of fields supported by this API call
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_twingle_donation_DoubleOptInConfirm_spec(&$params)
{
    $params['project_id'] = array(
        'name' => 'project_id',
        'title' => E::ts('Project ID'),
        'type' => CRM_Utils_Type::T_STRING,
        'api.required' => 1,
        'description' => E::ts('The Twingle project ID.'),
    );
    $params['user_email'] = array(
        'name' => 'user_email',
        'title' => E::ts('Email address'),
        'type' => CRM_Utils_Type::T_STRING,
        'api.required' => 1,
        'description' => E::ts('The e-mail address of the contact.')
    );
}

/**
 * TwingleDonation.Cancel API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_twingle_donation_DoubleOptInConfirm($params)
{
    // Log call if debugging is enabled within civicrm.settings.php.
    if (defined('TWINGLE_API_LOGGING') && TWINGLE_API_LOGGING) {
        CRM_Core_Error::debug_log_message('TwingleDonation.DoubleOptInConfirm: ' . json_encode($params, JSON_PRETTY_PRINT));
    }

    try {
        // Get the profile defined for the given form ID, or the default profile
        // if none matches.
        $profile = CRM_Twingle_Profile::getProfileForProject($params['project_id']);

        // Get the newsletter groups defined in the profile
        $newsletter_groups = $profile->getAttribute('newsletter_groups');

        // Extract user email from API call
        if (!empty($params['user_email'])) {
            $contacts = civicrm_api3('Email', 'get', array(
                'seqential' => 1,
                'email' => $params['user_email'],
            ));

            // Get pending group memberships for user
            if (!empty($contacts['values'])) {
                foreach ($contacts['values'] as $contact) {
                    $groups = civicrm_api3('GroupContact', 'get', array(
                        'sequential' => 1,
                        'contact_id' => $contact['contact_id'],
                        'status' => "Pending",
                    ));

                    // Only in newsletter groups: change group membership from pending to added
                    if (!empty($groups['values'])) {
                        foreach ($groups['values'] as $group) {
                            if (in_array($group['group_id'], $newsletter_groups)) {
                                civicrm_api3('GroupContact', 'create', array(
                                    'group_id' => $group['group_id'],
                                    'contact_id' => $contact['contact_id'],
                                    'status' => "Added",
                                    $result_values['groups'][] = $group['group_id'],
                                ));
                                // Display message if group membership was confirmed correctly
                                $result_values['double_opt_in'][$group['group_id']] = "Subscription confirmed";
                            }
                        }
                        // Display message if there is no pending group membership
                    } else {
                        $result_values['double_opt_in'][] = "Could not confirm subscription: No pending group membership";
                    }
                }
                // Display message if email can't be found
            } else {
                $result_values['double_opt_in'][] = "Could not confirm subscription: Email not found";
            }
        }
        $result = civicrm_api3_create_success($result_values);
    } catch (Exception $exception) {
        $result = civicrm_api3_create_error($exception->getMessage());
    }

    return $result;
}
