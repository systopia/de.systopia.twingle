{*------------------------------------------------------------+
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
+-------------------------------------------------------------*}

<div class="crm-block crm-form-block crm-twingle-form-block">

  <h3>Twingle API - Generic Settings</h3>

  <table class="form-layout-compressed">
    <tr class="crm-twingle-form-block-use-sepa">
      <td class="label">
        {$form.twingle_use_sepa.label}
        {help id="id-twingle_use_sepa" title=$form.twingle_use_sepa.label}
      </td>
      <td>
        {$form.twingle_use_sepa.html}
      </td>
    </tr>

    <tr class="crm-twingle-form-block-use-sepa-reference">
      <td class="label">
        {$form.twingle_dont_use_reference.label}
        {help id="id-twingle_dont_use_reference" title=$form.twingle_dont_use_reference.label}
      </td>
      <td>
        {$form.twingle_dont_use_reference.html}
      </td>
    </tr>

    <tr class="crm-twingle-form-block-prefix">
      <td class="label">{$form.twingle_prefix.label}
        {help id="id-twingle_prefix" title=$form.twingle_prefix.label}
      </td>
      <td>
        {$form.twingle_prefix.html}
      </td>
    </tr>

    <tr class="crm-twingle-form-block-recurring-protection">
      <td class="label">{$form.twingle_protect_recurring.label}
        {help id="id-twingle_protect_recurring" title=$form.twingle_protect_recurring.label}
      </td>
      <td>
        {$form.twingle_protect_recurring.html}
      </td>
    </tr>

    <tr class="crm-twingle-form-block-recurring-protection-activity">
      <td class="label">{$form.twingle_protect_recurring_activity_type.label}</td>
      <td>
        {$form.twingle_protect_recurring_activity_type.html}
      </td>
    </tr>

    <tr class="crm-twingle-form-block-recurring-protection-activity">
      <td class="label">{$form.twingle_protect_recurring_activity_subject.label}</td>
      <td>
        {$form.twingle_protect_recurring_activity_subject.html}
      </td>
    </tr>

    <tr class="crm-twingle-form-block-recurring-protection-activity">
      <td class="label">{$form.twingle_protect_recurring_activity_status.label}</td>
      <td>
        {$form.twingle_protect_recurring_activity_status.html}
      </td>
    </tr>

    <tr class="crm-twingle-form-block-recurring-protection-activity">
      <td class="label">{$form.twingle_protect_recurring_activity_assignee.label}</td>
      <td>
        {$form.twingle_protect_recurring_activity_assignee.html}
      </td>
    </tr>

  </table>

  <h3>Twingle Shop Integration</h3>

  <table class="form-layout-compressed">
    <tr class="crm-twingle-form-block-use-shop">
      <td class="label">{$form.twingle_use_shop.label}&nbsp;&nbsp;<a onclick='CRM.help("{$form.twingle_use_shop.label}", {literal}{"id":"id-{/literal}{$form.twingle_use_shop.name}{literal}","file":"CRM\/Twingle\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.twingle"}Help{/ts}" class="helpicon"></a></td>
      <td>
          {$form.twingle_use_shop.html}
        <br />
        <span class="description">
          {$formElements.twingle_use_shop.description}
        </span>
      </td>
    </tr>
    <tr class="crm-twingle-form-block-access-key twingle-shop-element">
      <td class="label">{$form.twingle_access_key.label}&nbsp;&nbsp;<a onclick='CRM.help("{$form.twingle_access_key.label}", {literal}{"id":"id-{/literal}{$form.twingle_access_key.name}{literal}","file":"CRM\/Twingle\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.twingle"}Help{/ts}" class="helpicon"></a></td>
      <td>
          {$form.twingle_access_key.html}
        <br />
        <span class="description">
          {$formElements.twingle_access_key.description}
        </span>
      </td>
    </tr>
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>

{literal}
  <script>
    /**
     * Will show/hide the twingle_protect_recurring_activity_* fields based on
     *  whether activity creation is selected
     */
    function twingle_protect_recurring_change() {
      if (cj('#twingle_protect_recurring').val() == '2') {
        cj('tr.crm-twingle-form-block-recurring-protection-activity').show();
      }
      else {
        cj('tr.crm-twingle-form-block-recurring-protection-activity').hide();
      }
    }

    cj(document).ready(function () {
      cj('#twingle_protect_recurring').change(twingle_protect_recurring_change);
      twingle_protect_recurring_change();
    });
  </script>
{/literal}
