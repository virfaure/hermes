<?php 

require_once 'init.php'; 

require_once '../inc/HermesHelper.php';
$helper = new HermesHelper();

$pathMagento = $helper->getMagentoRootPath();
$pathAppMagento = $pathMagento.'/app/Mage.php';
$project = $helper->getProject();

$hasFoundPath = false;
if(is_file($pathAppMagento)){
    require_once $pathAppMagento;
    Mage::app();
    $hasFoundPath = true;
}
    
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
    <meta charset='utf-8'>
    <title>Exportación de Productos o Categorías</title>
	<link rel="stylesheet" href="styles/styles.css" type="text/css">
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
        <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/jquery-ui.min.js"></script>
	<script src="js/custom.js"></script>
</head>
<body>
	<div id="container">
		<h1>Exportación de Productos o Categorías</h1>
						
		<div class="explanation">
			<p>Esta herramienta permite exportar csv de categorías y/o productos.</p>	
		</div>
		
		<hr />
        
         <p>
            <label>1 - Seleccionar la vista de tienda :</label>
            <select id="store_id" name="store_id" style="width:20%">
                <option value="">-- Seleccionar --</option>
               <?php
                    if($hasFoundPath){
                        echo "<option value='admin'>Admin</option>";
                        foreach (Mage::app()->getStores() as $store) {
                            echo "<option value='".$store->getId()."'>".$store->getCode()."</option>";
                        }
                    }
                ?>     
            </select>
        </p>
        <p>
            <label>2 - Seleccionar el tipo de exportación :</label>
        </p>
                   
        <div class="box">
            <div>Exportar Productos o Imagenes de Productos : </div><br />
            Filtar Por Atributo (Opcional):
            <?php
                if($hasFoundPath){
                    echo '<select id="attribute_code" name="attribute_code" style="width:20%; margin-right:5px;">
                                <option value="">-- Seleccionar --</option>';
                    $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection')->setOrder('attribute_code', 'ASC');
                    foreach ($productAttrs as $productAttr) { 
                        echo "<option title='".$productAttr->getFrontendInput()."' value='".$productAttr->getAttributeCode()."'>".$productAttr->getAttributeCode()."</option>";
                    }

                    echo '</select>';
                    
                    echo '<input type="text" id="value_attribute" name="value_attribute">';
                }
            ?>  
            <br />
            <input type="button" id="export_product" name="export_product" value="Exportar Productos" style="margin-right:50px;" class="btn btn-blue big btn-export">
            <input type="button" id="export_product_image" name="export_product_image" value="Exportar Imagenes de los Productos" style="margin-right:50px;" class="btn btn-blue big btn-export">
        </div>
                
        <div style="border:1px solid #eee; padding:10px; margin-top:10px;">
            <div>Exportar Categorías: </div>
            <div style="margin:10px 0;"><input type="checkbox" id="only_seo_fields" name="only_seo_fields" value="1" checked="checked"> Sólo los campos SEO</div>
            <input type="button" id="export_category" name="export_category" value="Exportar Categorías" class="btn btn-blue big btn-export">
	</div>	
        <br /> <br />
        
		<div id="loading-border">
			<div id="loading"></div>
		</div>
	</div>	
</body>
</html>
