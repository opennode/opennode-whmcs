<script type="text/javascript" src="includes/jscript/jqueryui.js"></script>
<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/uistyle.css" />

<div id="order-comparison">

<h1>{$LANG.cartbrowse}</h1>

<div class="cartcats">
{foreach key=num item=productgroup from=$productgroups}
{if $gid eq $productgroup.gid}
{$productgroup.name} |
{else}
<a href="{$smarty.server.PHP_SELF}?gid={$productgroup.gid}">{$productgroup.name}</a> |
{/if}
{/foreach}
{if $loggedin}
<a href="{$smarty.server.PHP_SELF}?gid=addons">{$LANG.cartproductaddons}</a> |
{if $renewalsenabled}<a href="{$smarty.server.PHP_SELF}?gid=renewals">{$LANG.domainrenewals}</a> | {/if}
{/if}
{if $registerdomainenabled}<a href="{$smarty.server.PHP_SELF}?a=add&domain=register">{$LANG.registerdomain}</a> | {/if}
{if $transferdomainenabled}<a href="{$smarty.server.PHP_SELF}?a=add&domain=transfer">{$LANG.transferdomain}</a> | {/if}
<a href="{$smarty.server.PHP_SELF}?a=view">{$LANG.viewcart}</a>
</div>

{if !$loggedin && $currencies}
<div class="currencychooser">
{foreach from=$currencies item=curr}
<a href="cart.php?gid={$gid}&currency={$curr.id}"><img src="images/flags/{if $curr.code eq "AUD"}au{elseif $curr.code eq "CAD"}ca{elseif $curr.code eq "EUR"}eu{elseif $curr.code eq "GBP"}gb{elseif $curr.code eq "INR"}in{elseif $curr.code eq "JPY"}jp{elseif $curr.code eq "USD"}us{elseif $curr.code eq "ZAR"}za{else}na{/if}.png" border="0" alt="" /> {$curr.code}</a>
{/foreach}
</div>
<div class="clear"></div>
{/if}

{if count($products.0.features)}
<div class="prodtablecol">
<div class="featureheader"></div>
{foreach from=$products.0.features key=feature item=value}
<div class="feature">{$feature}</div>
{/foreach}
</div>
{/if}

{foreach key=num item=product from=$products}
<div class="prodtablecol">
<div class="{if $num % 2 == 0}a{else}b{/if}header{if !count($products.0.features)}expandable{/if}">
<span class="title">{$product.name}</span><br />
{assign var="OMS_BUNDLE_ID" value="4"}
{if $product.bid}
    {if $gid eq $OMS_BUNDLE_ID}
       {oms_bundle_products groupId=$gid bundleId=$product.bid}
       {if $product.displayprice} {$product.displayprice} {$LANG.orderpaymenttermonetimebundleperhour}{else} {$productSum} {$LANG.bundleeurperhour}  {/if} 
       
       {if $loggedin}
             <br/>{$LANG.orderpaymenttermonetimebundlestart} {oms_credit_time eurPerHour=$product.displayprice} {$LANG.orderpaymenttermonetimebundleend}
       {/if}
    {else}
        {$LANG.bundledeal}{if $product.displayprice} {$product.displayprice}{/if}
    {/if}
{elseif $product.paytype eq "free"}
{$LANG.orderfree}
{elseif $product.paytype eq "onetime"}
{$product.pricing.onetime} {$LANG.orderpaymenttermonetime}<br />
{else}
{$product.pricing.monthly}
{/if}<br />
</div>
{foreach from=$product.features key=feature item=value}
<div class="{if $num % 2 == 0}a{else}b{/if}feature{cycle name=$product.pid values="1,2"}">{$value}</div>
{foreachelse}
    {if $gid eq $OMS_BUNDLE_ID}
        {if $product.description}
            <div class="{if $num % 2 == 0}a{else}b{/if}featuredesc{cycle name=$product.pid values="1,2"}">{$product.description}</div>
        {else}
           <div class="{if $num % 2 == 0}a{else}b{/if}featuredesc{cycle name=$product.pid values="1,2"}">
           {foreach from=$productNames item=pName}
                {$pName}<br/>
           {/foreach}
           </div>
        {/if}
    {else}
        <div class="{if $num % 2 == 0}a{else}b{/if}featuredesc{cycle name=$product.pid values="1,2"}">
            {$product.description}<br/>
            {oms_bundle_credit_time groupId=$OMS_BUNDLE_ID}
            {foreach from=$bundleNameAndSum item=bundle}
                <b>{$bundle.name}</b>  {oms_credit_time eurPerHour=$bundle.price  credit=$product.pricing.onetime digits=2} {$LANG.orderpaymenttermonetimebundleend}<br/>
            {/foreach}
        </div>
    {/if}
{/foreach}
<div class="{if $num % 2 == 0}a{else}b{/if}feature{cycle name=$product.pid values="1,2"}">
<br />
<input type="button" value="{$LANG.ordernowbutton} &raquo;"{if $product.qty eq "0"} disabled{/if} onclick="window.location='{$smarty.server.PHP_SELF}?a=add&{if $product.bid}bid={$product.bid}{else}pid={$product.pid}{/if}'" class="cartbutton" />
<br /><br />
</div>
</div>
{if !count($products.0.features) && ($num+1) % 5 == 0}<div class="clear"></div>
{/if}
{/foreach}

<div class="clear"></div>

</div>