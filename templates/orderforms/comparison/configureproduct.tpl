<script type="text/javascript" src="includes/jscript/jqueryui.js"></script>
<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/uistyle.css" />

<div id="order-comparison">

{include file="orderforms/comparison/comparisonsteps.tpl" step=2}

<div class="cartcontainer">

<form method="post" action="cart.php?a=confproduct" id="orderfrm">
<input type="hidden" name="configure" value="true" />
<input type="hidden" name="i" value="{$i}" />

{if $errormessage}<div class="errorbox">{$errormessage}</div><br />{/if}

<input type="hidden" name="previousbillingcycle" value="{$billingcycle}" />
{if $pricing.type eq "recurring"}
<h2>{$LANG.cartchoosecycle|strtolower}</h2>
<div class="billingcycle">
<table width="100%" cellspacing="0" cellpadding="0" class="configtable">
{if $pricing.monthly}<tr><td class="radiofield"><input type="radio" name="billingcycle" id="cycle1" value="monthly"{if $billingcycle eq "monthly"} checked{/if} onclick="submit()" /></td><td class="fieldarea"><label for="cycle1">{$pricing.monthly}</label></td></tr>{/if}
{if $pricing.quarterly}<tr><td class="radiofield"><input type="radio" name="billingcycle" id="cycle2" value="quarterly"{if $billingcycle eq "quarterly"} checked{/if} onclick="submit()" /></td><td class="fieldarea"><label for="cycle2">{$pricing.quarterly}</label></td></tr>{/if}
 {if $pricing.semiannually}<tr><td class="radiofield"><input type="radio" name="billingcycle" id="cycle3" value="semiannually"{if $billingcycle eq "semiannually"} checked{/if} onclick="submit()" /></td><td class="fieldarea"><label for="cycle3">{$pricing.semiannually}</label></td></tr>{/if}
{if $pricing.annually}<tr><td class="radiofield"><input type="radio" name="billingcycle" id="cycle4" value="annually"{if $billingcycle eq "annually"} checked{/if} onclick="submit()" /></td><td class="fieldarea"><label for="cycle4">{$pricing.annually}</label></td></tr>{/if}
 {if $pricing.biennially}<tr><td class="radiofield"><input type="radio" name="billingcycle" id="cycle5" value="biennially"{if $billingcycle eq "biennially"} checked{/if} onclick="submit()" /></td><td class="fieldarea"><label for="cycle5">{$pricing.biennially}</label></td></tr>{/if}
{if $pricing.triennially}<tr><td class="radiofield"><input type="radio" name="billingcycle" id="cycle6" value="triennially"{if $billingcycle eq "triennially"} checked{/if} onclick="submit()" /></td><td class="fieldarea"><label for="cycle6">{$pricing.triennially}</label></td></tr>{/if}
</table>
</div>
{else}
<input type="hidden" name="billingcycle" value="{$billingcycle}" />
{/if}

{if $productinfo.type eq "server"}
<h2>{$LANG.cartconfigserver|strtolower}</h2>
<div class="serverconfig">
<table width="100%" cellspacing="0" cellpadding="0" class="configtable">
<tr><td class="fieldlabel">{$LANG.serverhostname}:</td><td class="fieldarea"><input type="text" name="hostname" size="15" value="{$server.hostname}" /> eg. server1(.yourdomain.com)</td></tr>
<input type="hidden" name="ns1prefix" size="10" value="ns1" />
<input type="hidden" name="ns2prefix" size="10" value="ns2" />
<tr><td class="fieldlabel">{$LANG.serverrootpw}:</td><td class="fieldarea"><input type="password" name="rootpw" size="20" value="{$server.rootpw}" /></td></tr>
</table>
</div>
{/if}

{if $configurableoptions}
<h2>{$LANG.orderconfigpackage|strtolower}</h2>
<div class="configoptions">
<table width="100%" cellspacing="0" cellpadding="0" class="configtable">
{foreach from=$configurableoptions item=configoption}
<tr><td class="fieldlabel">{$configoption.optionname}:</td><td class="fieldarea">
{if $configoption.optiontype eq 1}
<select name="configoption[{$configoption.id}]" onchange="recalctotals()">
{foreach key=num2 item=options from=$configoption.options}
<option value="{$options.id}"{if $configoption.selectedvalue eq $options.id} selected="selected"{/if}>{$options.name}</option>
{/foreach}
</select>
{elseif $configoption.optiontype eq 2}
{foreach key=num2 item=options from=$configoption.options}
<label><input type="radio" name="configoption[{$configoption.id}]" id="co{$options.id}" value="{$options.id}"{if $configoption.selectedvalue eq $options.id} checked="checked"{/if} onclick="recalctotals()" /> {$options.name}</label><br />
{/foreach}
{elseif $configoption.optiontype eq 3}
<label><input type="checkbox" name="configoption[{$configoption.id}]" id="co{$configoption.options.0.id}" value="1"{if $configoption.selectedqty} checked{/if} onclick="recalctotals()" /> {$configoption.options.0.name}</label>
{elseif $configoption.optiontype eq 4}
{if $configoption.qtymaximum}
{literal}
    <script>
    jQuery(function() {
        {/literal}
        var configid = '{$configoption.id}';
        var configmin = {$configoption.qtyminimum};
        var configmax = {$configoption.qtymaximum};
        var configval = {if $configoption.selectedqty}{$configoption.selectedqty}{else}{$configoption.qtyminimum}{/if};
        {literal}
        jQuery( "#slider"+configid ).slider({
            min: configmin,
            max: configmax,
            value: configval,
            range: "min",
            slide: function( event, ui ) {
                jQuery( "#confop"+configid ).val( ui.value );
                jQuery( "#confoplabel"+configid ).html( ui.value );
                recalctotals();
            }
        });
    });
    </script>
{/literal}
<table width="90%"><tr><td width="30" id="confoplabel{$configoption.id}" class="configoplabel">{if $configoption.selectedqty}{$configoption.selectedqty}{else}{$configoption.qtyminimum}{/if}</td><td><div id="slider{$configoption.id}"></div></td></tr></table>
<input type="hidden" name="configoption[{$configoption.id}]" id="confop{$configoption.id}" value="{if $configoption.selectedqty}{$configoption.selectedqty}{else}{$configoption.qtyminimum}{/if}" />
{else}
<input type="text" name="configoption[{$configoption.id}]" value="{$configoption.selectedqty}" size="5" onkeyup="recalctotals()" /> x {$configoption.options.0.name}
{/if}
{/if}
</td></tr>
{/foreach}
</table>
</div>
{/if}

{if $addons}
<h2>{$LANG.cartavailableaddons|strtolower}</h2>
<div class="addons">
<table width="100%" cellspacing="0" cellpadding="0" class="configtable">
{foreach from=$addons item=addon}
<tr><td class="radiofield"><input type="checkbox" name="addons[{$addon.id}]" id="a{$addon.id}"{if $addon.status} checked{/if} onclick="recalctotals()" /></td><td class="fieldarea"><label for="a{$addon.id}"><strong>{$addon.name}</strong> - {$addon.description} ({$addon.pricing})</label></td></tr>
{/foreach}
</table>
</div>
{/if}

{if $customfields}
<h2>{$LANG.orderadditionalrequiredinfo|strtolower}</h2>
<div class="customfields">
<table width="100%" cellspacing="0" cellpadding="0" class="configtable">
{foreach key=num item=customfield from=$customfields}
<tr><td class="fieldlabel">{$customfield.name}:</td><td class="fieldarea">{$customfield.input} {$customfield.description}</td></tr>
{/foreach}
</table>
</div>
{/if}

<div class="ordersummary" id="producttotal"></div>

<div class="checkoutbuttonsleft"><input type="button" value="{$LANG.cartchooseanotherproduct|strtolower}" onclick="window.location='cart.php'" class="cartbutton" /> <input type="button" value="{$LANG.viewcart|strtolower}" onclick="window.location='cart.php?a=view'" class="cartbutton" /></div>
<div class="checkoutbuttonsright"><input type="submit" value="{$LANG.cartaddandcheckout|strtolower} &raquo;" class="cartbutton green" /></div>
<div class="clear"></div>

</form>

</div>

</div>

<script language="javascript">recalctotals();</script>