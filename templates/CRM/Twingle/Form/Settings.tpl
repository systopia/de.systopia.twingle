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

  {* HEADER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <table class="form-layout-compressed">
  {foreach from=$elementNames item=elementName}
    <tr class="crm-twingle-form-block-{$form.$elementName.name}">
      <td class="label">{$form.$elementName.label} &nbsp;<a onclick='CRM.help("{$form.$elementName.label}", {literal}{"id":"id-{/literal}{$fo/rm.$elementName.name}{literal}","file":"CRM\/Twingle\/Form\/Settings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.twingle"}Help{/ts}" class="helpicon"></a></td>
      <td>
        {$form.$elementName.html}
        <br />
        <span class="description">
          {$formElements.$elementName.description}
        </span>
      </td>
    </tr>
  {/foreach}
  </table>

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>
