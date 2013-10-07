<div class="styled_title">
        <h3>{$LANG.clientUsage.activitylog}</h3>
  </div>
<br />
<table class="table table-striped table-framed">
    <thead>
        <tr>
            <th class="textcenter">{$LANG.clientUsage.startTime}</th>
            <th class="textcenter">{$LANG.clientUsage.activeHours}</th>
            <th class="textcenter">{$LANG.clientUsage.nrOfVms}</th>
            <th class="textcenter">{$LANG.clientUsage.cores}</th>
            <th class="textcenter">{$LANG.clientUsage.memory} ({$LANG.gb})</th>
            <th class="textcenter">{$LANG.clientUsage.disc} ({$LANG.gb})</th>
            <th class="textcenter">{$LANG.clientUsage.price}</th>
            <th class="textcenter">{$LANG.clientUsage.cost}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$omsconfs item=conf}
	        <tr>
	        	<td class="textcenter">{$conf.begin}</td>
	        	<td class="textcenter">{$conf.hoursInBetween|round:"2"} hours</td>
	        	{if $conf.number_of_vms == 0}
		 		<td class="textcenter" colspan="4">{$LANG.clientUsage.no_resource}</td>
				{else}
	    	    <td class="textcenter">{$conf.number_of_vms}</td>
	            <td class="textcenter">{$conf.cores}</td>
	            <td class="textcenter">{$conf.memory}</td>
	            <td class="textcenter">{$conf.disk|round:"2"} {$LANG.gb}</td>
	            {/if}
	            <td class="textcenter">{$conf.cost|string_format:"%01.5f"} {$LANG.eur}</td>
	            <td class="textcenter">{$conf.price|string_format:"%01.2f"} {$LANG.clientUsage.priceMo}</td>
	        </tr>
        {/foreach}
    </tbody>
</table>