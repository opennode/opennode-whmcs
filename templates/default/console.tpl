<div id="information_box" style="display:none;">
    <br />
    <h3 align="center">Oops, you did something wrong, please log out and then log in again to see the console.
        If problem presists please <a href="/submitticket.php">contact support</a>.
    </h3>
</div>

<div id="console_container">
    {literal}
    <script type="text/javascript">//some javascipt here
    jQuery.ajax({
             url:    "console/bin/id",
             success: function(result) {
                        // we expect the worst
                      },
             error: function(result) {
                $("#console_container").css("display", "none");
                $("#information_box").css("display", "");
             },
             async:   false
        });
    </script>
    {/literal}

    <iframe src="{$oms_link}/index.html?embedded=true" style="border: 1px solid lightgray; width: 100%; height: 750px"/>
</div>

