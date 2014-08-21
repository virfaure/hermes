$(document).ready(function(){
	
    $(".btn-export").each(function(){
        $(this).click(function() {
            
            //Get which button has been clicked
            var nameBtn = $(this).attr("name");
            var action = nameBtn.replace(/export_/, "");
            action = action.charAt(0).toUpperCase() + action.slice(1);
            
            var re = /_([a-z])/;
            action = action.replace(re, function(v) { return v.toUpperCase(); });
            action = action.replace("_", "");
            
            
            var store = $("#store_id").val();

            if(store == "" || typeof store === "undefined"){
                alert("Por favor, selecciona una vista de tienda");
            }else{
                $("#loading-border").show();
                $("#loading").addClass("loading");
                $("#loading").html("Empezando Exportación. Por favor espere...");

                // Store Code
                var code = $("#store_id option:selected").text();
                var attribute_code = $("#attribute_code").val();
                var attribute_value = $("#value_attribute").val();
                
                //SEO Fields => Only for Category
                var seo_fields = $("#only_seo_fields").is(':checked');
                
                $.ajax({
                    type: "POST",
                    url: "ajaxExport"+action+".php",
                    data: { store: store, code: code, attribute_code: attribute_code, attribute_value: attribute_value, seo_fields:seo_fields},
                    success: function(msg){
                        var response = msg.split('|');
                        
                        if(response[0] == "success"){
                            $("#loading").removeClass("loading").addClass("done");
                        }else{
                            $("#loading").removeClass("loading").addClass("error");
                        }
                        $("#loading").html(response[1]);
                    }
                });
            } 
        });  
    });
    
    
    //Select Attribute Product 
    $("#attribute_code").change(function() {
  
        var store = $("#store_id").val();
        var disabled = false;
        
        var type_input = $(this).find(":selected").attr('title');
        var attribute_code = $(this).val();

        var select = $('<select id="value_attribute" name="value_attribute" class="half"></select>');
        var input = $('<input type="text" id="value_attribute" name="value_attribute" class="half">');
        

        if(type_input == 'select'){
            if(store == "" || typeof store === "undefined"){
                alert("Por favor, selecciona una vista de tienda");
                disabled = true;
            }
        
            $("#value_attribute").replaceWith(select).show();    
            $("#value_attribute").attr('disabled', disabled);

            //Fill Select with values
            $.ajax({
                type: "POST",
                url: "ajaxGetOptionValues.php",
                data: { attribute_code: attribute_code, store: store},
                success: function(msg){
                    var response = msg.split('|');

                    if(response[0] == "success"){
                        $("#value_attribute").html(response[1]);
                    }
                }
            });
        }else{
           $("#value_attribute").replaceWith(input).show();    
        }
    });
    
     //Hide Input Option
     $("#value_attribute").hide();     
     
     
     //Select Store 
     $("#store_id").change(function() {
         $("#value_attribute").attr('disabled', false);
         type_input = $('#value_attribute').attr('type');
         
         if(type_input != 'text'){
            var select = $('<select id="value_attribute" name="value_attribute" class="half"></select>');
            $("#value_attribute").replaceWith(select).show();    

            var store = $(this).val();
            var attribute_code = $("#attribute_code").val();

            //Fill Select with values
            $.ajax({
                type: "POST",
                url: "ajaxGetOptionValues.php",
                data: { attribute_code: attribute_code, store: store},
                success: function(msg){
                    var response = msg.split('|');

                    if(response[0] == "success"){
                        $("#value_attribute").html(response[1]);
                    }
                }
            });
        }
         
     });           
   
});


