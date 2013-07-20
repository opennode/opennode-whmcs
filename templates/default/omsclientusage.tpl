<table class="table table-striped table-framed">
    <thead>
        <tr>
            <th class="textcenter">{$LANG.clientUsage.startTime}</th>
            <th class="textcenter">{$LANG.clientUsage.activeHours}</th>
            <th class="textcenter">{$LANG.clientUsage.nrOfVms}</th>
            <th class="textcenter">{$LANG.clientUsage.cores}</th>
            <th class="textcenter">{$LANG.clientUsage.disc}</th>
            <th class="textcenter">{$LANG.clientUsage.memory}</th>
            <th class="textcenter">{$LANG.clientUsage.cost}</th>
            <th class="textcenter">{$LANG.clientUsage.price}</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$omsconfs item=conf}
	        <tr>
	        	<td class="textcenter">{$conf.begin}</td>
	        	<td class="textcenter">{$conf.hoursInBetween|round:"2"} hours</td>
	    	    <td class="textcenter">{$conf.number_of_vms}</td>
	            <td class="textcenter">{$conf.cores}</td>
	            <td class="textcenter">{$conf.disk|round:"2"} {$LANG.gb}</td>
	            <td class="textcenter">{$conf.memory} {$LANG.gb}</td>
	            <td class="textcenter">{$conf.cost|round:"5"} {$LANG.eur}</td>
	            <td class="textcenter">{$conf.price|round:"5"} {$LANG.clientUsage.priceMo}</td>
	        </tr>
        {/foreach}
    </tbody>
</table>