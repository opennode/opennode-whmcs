<table class="table table-striped table-framed">
    <thead>
        <tr>
            <th class="textcenter">Start time</th>
            <th class="textcenter">Active hours</th>
            <th class="textcenter">Nr of VMs</th>
            <th class="textcenter">Cores</th>
            <th class="textcenter">Disc</th>
            <th class="textcenter">Memory</th>

            <th class="textcenter">Cost</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$confs item=conf}
	        <tr>
	        	<td class="textcenter">{$conf.begin}</td>
	        	<td class="textcenter">{$conf.hoursInBetween|round:"2"} hours</td>
	    	    <td class="textcenter">{$conf.number_of_vms}</td>
	            <td class="textcenter">{$conf.cores}</td>
	            <td class="textcenter">{$conf.disk|round:"2"} GB</td>
	            <td class="textcenter">{$conf.memory} GB</td>
	            <td class="textcenter">{$conf.cost|round:"5"} EUR</td>
	        </tr>
        {/foreach}
    </tbody>
</table>