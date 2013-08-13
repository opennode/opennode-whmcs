<script type="text/javascript" src="includes/jscript/jqueryui.js"></script>
<script type="text/javascript" src="templates/orderforms/{$carttpl}/js/main.js"></script>
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />
<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/uistyle.css" />

<div id="order-comparison">

<h1>Pick a virtual machine package...</h1>


{if !$loggedin && $currencies}
<div class="currencychooser">
{foreach from=$currencies item=curr}
<a href="cart.php?gid={$gid}&currency={$curr.id}"><img src="images/flags/{if $curr.code eq "AUD"}au{elseif $curr.code eq "CAD"}ca{elseif $curr.code eq "EUR"}eu{elseif $curr.code eq "GBP"}gb{elseif $curr.code eq "INR"}in{elseif $curr.code eq "JPY"}jp{elseif $curr.code eq "USD"}us{elseif $curr.code eq "ZAR"}za{else}na{/if}.png" border="0" alt="" /> {$curr.code}</a>
{/foreach}
</div>
<div class="cartIntro"><h3>INTRO PLACEHOLDER</h3><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam non dolor neque. In hac habitasse platea dictumst. In hac habitasse platea dictumst. Duis tortor augue, venenatis gravida fermentum et, bibendum quis sapien. Sed tincidunt tincidunt elit, lacinia eleifend neque aliquam at. Nunc a nibh mi. Praesent at felis urna, facilisis euismod risus. Fusce cursus massa et dolor hendrerit placerat.
</p><p>
Pellentesque placerat mollis risus. Vestibulum at enim leo. In blandit purus id eros vulputate interdum. Maecenas luctus scelerisque dapibus. Morbi convallis ornare nisl et imperdiet. Proin at nisi diam. Nulla iaculis rhoncus urna nec hendrerit. Vivamus consectetur venenatis ante ac viverra. Nulla non tellus a velit feugiat viverra. Maecenas ut nisl a tellus facilisis tincidunt. Aliquam sollicitudin congue interdum.</p></div>
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
    {if $num == 3}
        <div class="clear" />
        <br />
        <h1>... or top up your credit</h1>
    {/if}
<div class="prodtablecol">
<div class="{if $num % 2 == 0}a{else}b{/if}header{if !count($products.0.features)}expandable{/if}">
<span class="title">{$product.name} </span><br />

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
    {if $gid eq $OMS_GENERATED_ID}
        {if not $product.name|strstr:"EUR"}
            {$product.pricing.onetime} {$LANG.bundleeurpermonth} <br />
        {/if}
    {else}
      {$product.pricing.onetime} {$LANG.orderpaymenttermonetime}<br />
    {/if}
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
        {if $num < 3}
        <div class="{if $num % 2 == 0}a{else}b{/if}featuredesc{cycle name=$product.pid values="1,2"}">
            {$product.description}<br/>
                {if $gid eq $OMS_GENERATED_ID}
                    Allows to run<br />
                    {foreach key=num item=bundle from=$products}
                        {if not $bundle.name|strstr:"EUR"}
                            {if $bundle.pid neq $product.pid}
                                <b>{$bundle.name}</b> for {oms_credit_time eurPerHour=$bundle.pricing.onetime  credit=$product.pricing.onetime digits=2} {$LANG.orderpaymenttermonetimebundleend}<br/>
                            {/if}
                        {/if}
                        
                    {/foreach}
                {else}
                    {oms_bundle_credit_time groupId=$OMS_BUNDLE_ID}
                    {foreach from=$bundleNameAndSum item=bundle}
                        <b>{$bundle.name}</b>  {oms_credit_time eurPerHour=$bundle.price  credit=$product.pricing.onetime digits=2} {$LANG.orderpaymenttermonetimebundleend}<br/>
                    {/foreach}
                {/if}
        </div>
        {/if}
    {/if}
{/foreach}

<div class="{if $num % 2 == 0}a{else}b{/if}feature{cycle name=$product.pid values="1,2"}">
<br />
<input type="button" value="{$LANG.ordernowbutton} &raquo;"{if $product.qty eq "0"} disabled{/if} onclick="window.location='{$smarty.server.PHP_SELF}?a=add&{if $product.bid}bid={$product.bid}{else}pid={$product.pid}{/if}'" class="cartbutton" />
<br /><br />

</div>
</div>

{/foreach}

<div class="clear"></div>

</div>