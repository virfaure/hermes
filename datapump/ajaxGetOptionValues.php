<?php

require_once '../inc/HermesHelper.php';
$helper = new HermesHelper();

$pathMagento = $helper->getMagentoRootPath();
$pathAppMagento = $pathMagento.'/app/Mage.php';
$project = $helper->getProject();

// Attribute Code
$attributeCode = null;
if(!empty($_POST['attribute_code'])){
    $attributeCode = $_POST['attribute_code'];
}

// Store Id
$storeID = null;
if(!empty($_POST['store'])){
    $storeID = $_POST['store'];
}

if(is_file($pathAppMagento)){
    if($storeID  && $attributeCode){
        // Get All option Values for attribute Code
        
        if($storeID == 'admin'){
            $storeID = 0;
        }
            
       
        try{
            require_once $pathAppMagento;
            Mage::app();
            
            $selectHtml = null;
            
            $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeCode);
            if ($attribute->getId() && $attribute->usesSource()) {
                $options = $attribute->setStoreId($storeID)->getSource()->getAllOptions(false);
                
                foreach($options as $option){
                    $selectHtml.="<option value='{$option['value']}'>{$option['label']}</option>";
                }
            }
            
            if(!empty($selectHtml)) echo "success|".$selectHtml;
            else echo "error|";
            
        }catch (Exception $e){
            echo "error|Problema con la exportación : " . $e->getMessage();
        }      
    }else{
        echo "error|Store ID invalid";
    }
}else{
	echo "error|File app/Mage.php not found";
}

