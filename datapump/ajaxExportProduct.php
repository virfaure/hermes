<?php

require_once '../inc/HermesHelper.php';
$helper = new HermesHelper();

$pathMagento = $helper->getMagentoRootPath();
$pathAppMagento = $pathMagento.'/app/Mage.php';
$project = $helper->getProject();

// Store ID
$storeID = null;
if(!empty($_POST['store'])){
    $storeID = $_POST['store'];
    $storeCode = $_POST['code'];
}

if(is_file($pathAppMagento)){
    if($storeID){
        if($storeID == 'admin'){
            $storeID = 0;
        }
        
        // Export All Products in CSV
        // id, name
        $first = true;
        
        try{
            require_once $pathAppMagento;
            Mage::app();
            
            $filename = $project.'_export_product_'.date("Ymd_His").'_'.$storeCode.'.csv';
            $filepath = '../upload/'.$filename;
            $fp = fopen($filepath, 'w');
            
            $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
            
            //Get Attribute Code & Value Selected 
            $filterAttribute = false;
            if(!empty($_POST['attribute_code']) && !empty($_POST['attribute_value'])){
                $attributeCode = $_POST['attribute_code'];
                $attributeOptionValue = array();
                $attributeOptionValue = explode(",", $_POST['attribute_value']);
                $filterAttribute = true;
            }
            
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->setStoreId($storeID)
                //->setPage(1, 4)  //For tests
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('description')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('short_description')
                ->addAttributeToSelect('status')
                ->addAttributeToSelect('type_id')
                ->addAttributeToSelect('visibility')
                ->addAttributeToSelect('attribute_set_id')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('special_price')
                ->addAttributeToSelect('tax_class_id')
                ->addAttributeToSelect('weight')
                ->addAttributeToSelect('category_ids');
            
            
            //Add All Custom attribute
            $customAttribute = array();
            $customProductAttributes = Mage::getResourceModel('catalog/product_attribute_collection');
            foreach ($customProductAttributes as $productAttribute) { 
                if($productAttribute->getIsUserDefined() == 1){
                    $customAttribute[] = $productAttribute->getAttributeCode();
                    $collection->addAttributeToSelect($productAttribute->getAttributeCode());
                }
            }
            
            //Add Search Attribute if not in custom ones
            if($filterAttribute){
                // Add serach attribute to collection if not custom user
                if(!in_array($attributeCode, $customAttribute)) $collection->addAttributeToSelect($attributeCode);
                
                if($attributeCode == "category_ids"){
                    $collection->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left');
                    $collection->addAttributeToFilter('category_id', array('in' => $attributeOptionValue));
                }else{
                    $collection->addAttributeToFilter($attributeCode, array('in' => $attributeOptionValue));
                }
            }
            
            //Add Stock Item 
            $collection->joinTable(
                array('cisi' => 'cataloginventory/stock_item'),
                'product_id=entity_id',
                array(
                    'qty' => 'qty',
                    'is_in_stock' => 'is_in_stock'
                ),
                null,
                'left'
            );
            
            //echo $collection->getSelect()->__toString();
            //exit();
            
            foreach($collection as $product){		
                $attributeSetModel->load($product->getAttributeSetId());
                $attributeSetName  = $attributeSetModel->getAttributeSetName();
                
                //$visibilityName = Mage_Catalog_Model_Product_Visibility::getOptionText($product->getVisibility());
                //$statusName = Mage_Catalog_Model_Product_Status::getOptionText($product->getStatus());
            	
                $taxClass = Mage::getModel('tax/class')->load($product->getTaxClassId());
                $taxClassName = $taxClass->getClassName();

                $related_product_collection = $product->getRelatedProductCollection()
                        ->addAttributeToSelect('sku')
                        ->addAttributeToSort('position', Varien_Db_Select::SQL_ASC)
                        ->addStoreFilter();
                   
                $re_skus = null;
                foreach($related_product_collection as $related){	
                    $re_skus[] = $related->getSku(); 	
                }
            
                            
                $data['entity_id'] = $product->getId();
                $data['sku'] = $product->getSku();
                $data['store'] = $storeCode;
                $data['name'] = $product->getName();
                $data['url'] = $product->getProductUrl();
                $data['url_key'] = $product->getUrlKey();
                $data['type'] = $product->getTypeId();
                $data['attribute_set'] = $attributeSetName;
                $data['status'] = $product->getStatus();
                $data['visibility'] = $product->getVisibility();
                $data['description'] = $product->getDescription();
                $data['short_description'] = $product->getShortDescription();
                $data['price'] = $product->getPrice();
                $data['special_price'] = $product->getSpecialPrice();
                $data['tax_class_id'] = $taxClassName;
                $data['weight'] = $product->getWeight();
                $data['qty'] = round($product->getQty());
                $data['is_in_stock'] = $product->getIsInStock();
                $data['category_ids'] = implode(",", $product->getCategoryIds());
                
                //Related 
                if(!empty($re_skus)){
                    $data['re_skus'] = implode(",", $re_skus);
                }
                
                //Simples Skus OR configurables attributes
                $simples_skus = $data['simples_skus'] = null;
                $configurable_attributes = $data['configurable_attributes'] = null;
                if($product->getTypeId() == "configurable"){
                    //Get simples_skus
                    $simpleProducts = $product->getTypeInstance()->getUsedProducts();
                    foreach($simpleProducts as $simple){
                        $simples_skus[] = $simple->getSku();
                    }
                    
                    if(count($simples_skus) > 0)  $data['simples_skus'] = implode(",", $simples_skus);
                    
                    $configurableAttributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
                    foreach($configurableAttributes as $confAttribute){
                        $configurable_attributes[] = $confAttribute->getProductAttribute()->getAttributeCode();
                    }
                    
                    if(count($configurable_attributes) > 0)  $data['configurable_attributes'] = implode(",", $configurable_attributes);
                }
                
                
                //Categories
                $countCat = count($product->getCategoryIds());
                $category = null;
                $cpt = 0;	
                foreach ($product->getCategoryIds() as $category_id) {	
                    $_cat = Mage::getModel('catalog/category')->load($category_id) ;
                    $category .= $_cat->getName();
                    if($cpt < $countCat - 1) $category .= ", ";
                    $cpt++;
                } 
                $data['categories'] = $category;
                
                // Add Custom attribute Values
                foreach ($customAttribute as $customAttributeCode) { 
                    if($product->getData($customAttributeCode)){
                        $attribute = $product->getResource()->getAttribute($customAttributeCode);
                        if($attribute->getFrontendInput() == "select"){
                            $data[$customAttributeCode] = $attribute->getSource()->getOptionText($product->getData($customAttributeCode));
                        }else{
                            $data[$customAttributeCode] = $product->getData($customAttributeCode);
                        }
                    }else{
                        $data[$customAttributeCode] = null;
                    }
                }
            
                // Add Search attribute Values
                if($filterAttribute && !in_array($attributeCode, $customAttribute)){
                    //$attribute = $product->getResource()->getAttribute($attributeCode);
                    //$data[$attributeCode] = $attribute->getSource()->getOptionText($attributeOptionValue);
                }
                
                
                if ($first) {
                    fputcsv($fp, array_keys($data), ';');
                    $first = false;
                }
                fputcsv($fp, array_values($data), ';');
            }
            fclose($fp);
            /*
            $period = strtotime("-1 day");
            $path_upload = $path[0].$hermesFolder."upload/";
            if ( $handle = opendir( $path_upload ) ) {
                while (false !== ($file = readdir($handle))) {
                    $filelastmodified = filemtime($path_upload.$file);
                                    
                    if($filelastmodified < $period && is_file($path_upload.$file))
                    {
                        unlink($path_upload.$file);
                    }
                }
                closedir($handle);
            }
            */
            
            echo "success|Descarga aqui tu fichero csv de exportación de productos : <a href='".$filepath."'>".$filename."</a>";
        }catch (Exception $e){
            echo "error|Problema con la exportación.";
        }     
    }else{
        echo "error|Store ID invalid";
    }   
}else{
	echo "error|File app/Mage.php not found";
}

