<p align="center">
    {$datebetween}
</p>
<table class="table table-striped table-framed">
    <thead>
        <tr>
            <th class="textcenter">user_id</th>
            <th class="textcenter">credit</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$clients item=client}
        <tr>
            <td>{$client.clientid}</td>
            <td>{$client.credit} EUR</td>
        </tr>
        {/foreach}
    </tbody>
</table>