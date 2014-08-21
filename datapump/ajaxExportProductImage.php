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
        // Export All Products in CSV
        // id, name
        $first = true;
        
        try{
            require_once $pathAppMagento;
            Mage::app();
            
            $filename = $project.'_export_image_product_'.date("Ymd_His").'_'.$storeCode.'.csv';
            $filepath = '../upload/'.$filename;
            $fp = fopen($filepath, 'w');
            
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
                ->addAttributeToSelect('thumbnail')
                ->addAttributeToSelect('small_image')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('sku');
            
            if($filterAttribute){
                $collection->addAttributeToFilter($attributeCode, array('in' => $attributeOptionValue));
            }
                    
            foreach($collection as $product){		

                $data['sku'] = $product->getSku();

                $mediaGallery = Mage::getModel('catalog/product')->load($product->getId())->getMediaGalleryImages();
                $add_images = '';
                
                $data['thumbnail'] =  $data['small_image'] =  $data['image'] = null;
                
                foreach ($mediaGallery as $image) {  
                    //separadas por comas. 
                    if (!$image['disabled']) $add_images .= $image['url'].',';
                }
                
                $data['thumbnail'] = $product->getMediaConfig()->getMediaUrl($product->getThumbnail());
                $data['small_image'] = $product->getMediaConfig()->getMediaUrl($product->getSmallImage());
                $data['image'] = $product->getMediaConfig()->getMediaUrl($product->getImage());
                
                // Remove last ';' from images list
                $add_images = substr_replace($add_images ,"",-1);
                $data['gallery'] =  $add_images;

                if ($first) {
                    fputcsv($fp, array_keys($data), ';');
                    $first = false;
                }
                fputcsv($fp, array_values($data), ';');
            }
            fclose($fp);
            
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

