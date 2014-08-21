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
        // Export All Categories in CSV
        // id, name
        $first = true;
        
        try{
            require_once $pathAppMagento;
            Mage::app();
            
            $filename = $project.'_export_category_'.date("Ymd_His").'_'.$storeCode.'.csv';
            $filepath = '../upload/'.$filename;
            $fp = fopen($filepath, 'w');
            
            $store = Mage::getModel('core/store')->load($storeID);
            $rootId = $store->getRootCategoryId();
            
            $collection = Mage::getModel('catalog/category')->getCollection()
                ->setStoreId($storeID)
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('description')
                ->addAttributeToSelect('meta_title')
                ->addAttributeToSelect('meta_description')
                ->addAttributeToSelect('meta_keywords')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('parent_id');
                                
            if($_POST['seo_fields'] == 'false'){
                $collection->addAttributeToSelect('path')
                    ->addAttributeToSelect('parent_id')
                    ->addAttributeToSelect('position')
                    ->addAttributeToSelect('is_active')
                    ->addAttributeToSelect('include_in_menu');
                    
                $collection->setOrder('path','ASC');
            }
                
            foreach($collection as $category){
                if($category->getId() == $rootId) continue;
                
                $data['category_id'] = $category->getId();
                $data['store'] = $storeCode;
                $data['path_name'] = implode("/", getParentNames($rootId, $category));
                $data['name'] = $category->getName();
                $data['description'] = $category->getDescription();
                $data['url_key'] = $category->getUrlKey();
                $data['meta_title'] = $category->getMetaTitle();
                $data['meta_description'] = $category->getMetaDescription();
                $data['meta_keywords'] = $category->getMetaKeywords();

                if($_POST['seo_fields'] == 'false'){
                    $data['path'] = $category->getPath();
                    $data['parent_id'] = $category->getParentId();
                    $data['position'] = $category->getPosition();
                    $data['is_active'] = $category->getIsActive();
                    $data['include_in_menu'] = $category->getIncludeInMenu();
                }
            
                if ($first) {
                    fputcsv($fp, array_keys($data), ';');
                    $first = false;
                }
                fputcsv($fp, array_values($data), ';');
            }
            fclose($fp);
            
            echo "success|Descarga aqui tu fichero csv de exportación de categorías : <a href='".$filepath."'>".$filename."</a>";
        }catch (Exception $e){
            echo "error|Problema con la exportación : " . $e->getMessage();
        }      
    }else{
        echo "error|Store ID invalid";
    }
}else{
	echo "error|File app/Mage.php not found";
}

function getParentNames($rootId, $_category) {
    
    if($_category->getId()) {
        $name = array($_category->getName());
        if($_category->getParentId() == $rootId) {
            return $name;
        } else {
            $parentCatModel = Mage::getModel('catalog/category')->load($_category->getParentId());
            return array_merge(getParentNames($rootId, $parentCatModel), $name);
        }
    }
    return array();
}

