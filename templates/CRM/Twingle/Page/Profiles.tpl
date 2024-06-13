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

<div class="crm-block crm-content-block crm-twingle-content-block">

  <div class="crm-submit-buttons">
    <a href="{crmURL p="civicrm/admin/settings/twingle/profile" q="op=create"}" title="{ts domain="de.systopia.twingle"}New profile{/ts}" class="button">
      <span><i class="crm-i fa-plus-circle"></i> {ts domain="de.systopia.twingle"}New profile{/ts}</span>
    </a>
  </div>

  {if !empty($profiles)}
    <table>
      <thead>
      <tr>
        <th>{ts domain="de.systopia.twingle"}Profile name{/ts}</th>
        <th>{ts domain="de.systopia.twingle"}Selectors{/ts}</th>
        {if $twingle_use_shop eq 1}
        <th>{ts domain="de.systopia.twingle"}Shop Integration{/ts}</th>
        {/if}
        <th>{ts domain="de.systopia.twingle"}Used{/ts}</th>
        <th>{ts domain="de.systopia.twingle"}Last Used{/ts}</th>
        <th>{ts domain="de.systopia.twingle"}Operations{/ts}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$profiles item=profile}
        {assign var="profile_id" value=$profile.id}
        {assign var="profile_name" value=$profile.name}
        <tr class="twingle-profile-list">
          <td>{$profile.name}</td>
          <td>
              {if not $profile.is_default}
                <ul>
                    {foreach from=$profile.selectors item=selector}
                      <li><strong></strong> {$selector}</li>
                    {/foreach}
                </ul>
              {/if}
          </td>
          {if $twingle_use_shop eq 1}
          <td>{if $profile.enable_shop_integration}<span style="color:green">{ts domain="de.systopia.twingle"}enabled{/ts}</span>{else}<span>{ts domain="de.systopia.twingle"}disabled{/ts}</span>{/if}</td>
          {/if}
          <td>{ts domain="de.systopia.twingle"}{$profile_stats.$profile_name.access_counter_txt}{/ts}</td>
          <td>{ts domain="de.systopia.twingle"}{$profile_stats.$profile_name.last_access_txt}{/ts}</td>
          <td>
            <a href="{crmURL p="civicrm/admin/settings/twingle/profile" q="op=edit&id=$profile_id"}" title="{ts domain="de.systopia.twingle" 1=$profile.name}Edit profile %1{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.twingle"}Edit{/ts}</a>
            <a href="{crmURL p="civicrm/admin/settings/twingle/profile" q="op=copy&source_id=$profile_id"}" title="{ts domain="de.systopia.twingle" 1=$profile.name}Copy profile %1{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.twingle"}Copy{/ts}</a>
            {if $profile_name == 'default'}
              <a href="{crmURL p="civicrm/admin/settings/twingle/profile" q="op=delete&id=$profile_id"}" title="{ts domain="de.systopia.twingle" 1=$profile.name}Reset profile %1{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.twingle"}Reset{/ts}</a>
            {else}
              <a href="{crmURL p="civicrm/admin/settings/twingle/profile" q="op=delete&id=$profile_id"}" title="{ts domain="de.systopia.twingle" 1=$profile.name}Delete profile %1{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.twingle"}Delete{/ts}</a>
            {/if}
          </td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  {/if}

</div>
