<h2 align="center">
    Current VMs (as <a href="{$upload_folder}/omssstats.csv">CSV</a>)
</h2>
<table class="table table-striped table-framed">
    <thead>
        <tr>
            <th class="textcenter">VM hostname</th>
            <th class="textcenter">Owner (user_id)</th>
            <th class="textcenter">Cores</th>
            <th class="textcenter">RAM (GB)</th>
            <th class="textcenter">Disk (GB)</th>
            <th class="textcenter">IP</th>
        </tr>
    </thead>
    <tbody>

        {foreach from=$vms item=v}
        <tr>
            <td>{$v.0}</td>
            <td>{$v.1} ({$v.2})</td>
            <td>{$v.5}</td>
            <td>{$v.3}</td>
            <td>{$v.4}</td>
            <td>{$v.6}</td>
        </tr>
        {/foreach}
    </tbody>
</table>