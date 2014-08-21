<?php
	require_once("../inc/magmi_defs.php");
	require_once("../integration/inc/magmi_datapump.php");

	/** Define a logger class that will receive all magmi logs **/
	class TestLogger
	{
		public function log($data,$type)
		{
			if($type == "warning"){
				echo "<div class='warning'>$data</div>";
			}
			/*if($type == "info"){
				echo "<div class='info'>$data</div>";
			}	*/
			if($type == "success"){
				echo "<div class='success'>$data</div>";
			}
			
			if($type == "error"){
				echo "<div class='success'>$data</div>";
			}
		}
	}
	
	$dp=Magmi_DataPumpFactory::getDataPumpInstance("productimport");
	$dp->beginImportSession("update_stock","update",new TestLogger());
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
    <meta charset='utf-8'>
	<title>Actualizacion de Stock</title>
	<style>
		body{ font: 12px/1.35 Arial,Helvetica,sans-serif;}
		ul{list-style: none outside none;}
		div.explanation{font-size:11px; padding:10px; color:#2D282E;}
		form{width:50%; padding:5px;}
		
		.info, .success, .warning, .error, .validation {
			border: 1px solid;
			margin: 10px 0px;
			padding:15px 10px 15px 50px;
			background-repeat: no-repeat;
			background-position: 10px center;
		}
		.warning {color: #9F6000;background-color: #FEEFB3;background-image:url('warning.png');}
		.error {color: #D8000C; background-color: #FFBABA; background-image:url('error.png');}
		.success {color: #4F8A10;background-color: #DFF2BF;background-image:url('success.png');}
		.info {color: #00529B;background-color: #BDE5F8;background-image:url('info.png');}
	</style>
</head>
<body>
	<h1>Sube tu fichero csv para la actualizacion de stock</h1>
	<div class="explanation">
		El archivo de actualizacion de stock es un csv con un punto y coma (;) como caracter de separacion, y 3 columnas obligatorias :
		<ul>
			<li>- brand : Nombre de vuestra empresa (Recordad este nombre ya que siempre debe ser el mismo. Ej:BASI, BIJOUX_INDISCRET, CHESCO, MAR_NUA, SMASH, SUTRAN, TORRAS, XIEL, etc)</li>
			<li>- sku: código único para el producto. Este es el identificador del producto que se actualizará el stock.</li>
			<li>- qty: cantidad actual de stock del producto. También se aceptan valores tipo -1 o +1, donde se sumará o restará el valor indicado a la cantidad guardada en la web.</li>
		</ul>
		Si necesitas ayuda, puedes enviar un mail a <a href="mailto:tecnico@theetailers.com">tecnico@theetailers.com</a>.<br /><br />
		Aqui tienes un ejemplo de un fichero vacio : <a href="update_stock.xls">Descargar el ejemplo de xls</a>
		<div style="color:#ce0000; font-weight:bold;">Una vez modificado el fichero xls de ejemplo, tienes que guardarlo al formato CSV : Guardar Como -> CSV (delimitado por comma). </div>
	</div>
	<?php
	$error_upload = false;
	$msg_error = "";
	
	if( isset($_POST['upload']) )	{
	
		$content_dir = $_SERVER["DOCUMENT_ROOT"]. '/hermes/upload/';
		
		$tmp_file = $_FILES['stock_csv']['tmp_name'];

		if( !is_uploaded_file($tmp_file) )
		{
			$msg_error .= "Error al subir el archivo";
			$error_upload = true;
		}

		/* TYPE
		$type_file = $_FILES['stock_csv']['type'];
		if( !strstr($type_file, 'csv') && !strstr($type_file, 'text/plain') )
		{
			if(strstr($type_file, 'ms-excel')) $msg_error .= "El archivo es un EXCEL (xls, xlsx)<br /><div style='color:#ce0000; font-weight:bold;'>Tienes que guardarlo al formato CSV : Guardar Como -> CSV (delimitado por comma). </div>";
			else $msg_error .= "El archivo no es un CSV.";
			
			$error_upload = true;
		}
		*/

		$name_file = $_FILES['stock_csv']['name']."_".time();

		if( !move_uploaded_file($tmp_file, $content_dir . $name_file) )
		{
			$msg_error .= "Se ha producido un error a subir el archivo.";
			$error_upload = true;
		}

		if(!$error_upload){
			echo "El archivo subio con exito<br />";
			
			$file = $content_dir . $name_file;
			
			//READ FILE
			$columnsMandatory = array("brand", "sku", "qty");
			$columnsCsv = array();
			$exists = 0;
			$row = 0;
			
			if (($handle = fopen($file, "r")) !== FALSE) {
				while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
					$num = count($data);
					
					if($row == 0){ //header column
						for ($c=0; $c < $num; $c++) {
							$columnsCsv[$c] = $data[$c];
						}	
						$result = array_diff($columnsMandatory, $columnsCsv);
						if(!empty($result)){
							exit("Faltan campos obligatorios en el csv: brand, sku, qty");
						}
					}else{
					
						$error = 0;
						$msg = "";
						$brand = "";
						
						for ($c=0; $c < $num; $c++) {
							$item[$columnsCsv[$c]] = $data[$c];
							
							if($columnsCsv[$c] == "brand"){
								$brand = strtoupper($data[$c])."_";
								if($brand == "XIEL_") $brand = ""; //XIEL has already the name of the brand in sku.
							}
							
							if($columnsCsv[$c] == "sku"){
								$sku = $brand.$data[$c];
								$item["sku"] = $sku;
								
								//Get Type : configurable or simple to force manage_stock to NO
								$type = $dp->getEngine()->getProductType($sku);
								
								if($type){
									$item["type"] = $type;
								}else{
									$error++;
									$msg .= "SKU ".$sku. " NOT FOUND <br / >";
								}
							}	
						}
						
						
						if($error == 0){
							unset($item["brand"]);
							$dp->ingest($item);
						}else{
							$msg .= "Error en la actualizacion del producto : $sku";
							echo "<div class='error'>$msg</div>";
						}
					}
					
					$row++;	
				}
				fclose($handle);
			}
			
			 $dp->endImportSession();
		}else{
			echo "<div class='error'>$msg_error</div>";
		}
	} ?>
	
	
	<form method="post" enctype="multipart/form-data" action="update_stock.php">
		<p>
			<input type="file" name="stock_csv">
			<input type="submit" name="upload" value="Subir">
		</p>
	</form>
		
</body>
</html>
