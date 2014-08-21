<script type="text/javascript" src="../queue/jquery.progressbar/js/jquery.js"></script>
<script type="text/javascript" src="../queue/jquery.progressbar/js/jquery.progressbar.min.js" async defer></script>
<script type="text/javascript">
$(document).ready(function() {
    $('#spaceused4').hide();
    var __update = setInterval(function() {
        $.getJSON('../queue/get_status.php', function(data) {
            if(data !== false) {
                if(data.started_at !== null) {
                    $('#spaceused4').show();
                    $('#statustxt').hide();
                    $("#spaceused4").progressBar(data.percentage, { showText: true, barImage: '../queue/jquery.progressbar/images/progressbg_red.gif'} );
                } else {
                    $('#statustxt').html('Pending (in queue)');
                }
            } else {
                $('#statustxt').html('Ready').show();
                $('#spaceused4').hide();
                //Stop updating status once queue job finishes
                clearInterval(__update);
            }
        });}, 15000 //Refresh status each 15 seconds
        );

});
</script>
<div id="queue_info">
     <div>
            Reindex Status: &nbsp; <span id='statustxt'>Ready</span><span class="progressBar" id="spaceused4">0%</span>
         </table>
     </div>
</div>
