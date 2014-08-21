<div class="plugin_description">
This plugin automatically resets the stock of all the existing products (or a subset) before the importation.
    <div class="fieldinfo">If enabled, will remove all existing qty data about the products of the specified filter. </div>
</div>
<div>You can specify a filter as far as the attribute is an entity_type_int and a product's attribute. <br/>
     Apply the reset to the following attribute values (leave the fields empty to apply for all the products):</div>
<ul class="formline">
    <li class="label">Attribute :</li>
    <li class="value">
        <input type="text" name="STOCKRESET:attribute" value="<?php echo $this->getParam("STOCKRESET:attribute")?>" style="width:400px">
        <div class="fieldinfo">Example: marca</div>
    </li>
</ul>
<ul class="formline">
    <li class="label">Value(s) :</li>
    <li class="value">
        <input type="text" name="STOCKRESET:values" value="<?php echo $this->getParam("STOCKRESET:values")?>" style="width:400px">
        <div class="fieldinfo">You can set several values separated by a comma (,). <br/>Example: PUNTO BLANCO, TORRAS</div>
    </li>
</ul>
<br/><br/>
