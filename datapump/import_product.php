<?php

require_once 'init.php';


$columnsMandatory = array('name', 'sku', 'status', 'visibility', 'description', 'short_description', 'price', 'tax_class_id', 'qty', 'type', 'attribute_set', 'weight');
$profile = isset($_GET['profile']) ? $_GET['profile'] : 'import';

//If user-agent is curl, don't output html
$html = (!isset($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'curl') === false);
if(!$html) {
    $logger = 'ConsoleLogger';
} else {
    $logger = 'HTMLLogger';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
    <meta charset='utf-8'>
    <title>Importación de Productos</title>
	<link rel="stylesheet" href="styles/styles.css" type="text/css">
</head>
<body>
    <?php include 'inc/queue_info.php';?>
	<div id="container">
		<h1>Importación de productos</h1>
						
		<div class="explanation">
			<p>Por favor, antes de empezar a subir su fichero, usted tiene que validar que sea un archivo <strong>CSV delimitado por coma</strong> con el formato de <strong>codificación UTF-8.</strong></p>
			<p><strong>Si necesita ayuda, por favor consulte el manual de importación : <a target="_blank" href="<?php echo $manual_url; ?>">Consultar el manual</a></strong> <strong>o consulte</strong> <a href="faq.php" target="_blank">las preguntas frecuentes</a></p>
			<p>Le aconsejamos usar <strong>esta plantilla del fichero de importación</strong>, la cual le servirá como ejemplo en caso de que no esté seguro de cómo rellenar los campos : <a href="import_product.xls">Descargar la plantilla</a></p>
			<div class="warning">Una vez modificado la plantilla xls, tiene que guardarlo al formato CSV : Guardar Como -> CSV (delimitado por coma) con el formato de codificación UTF-8.</div>
		</div>
		
		<hr />
	
<?php
}   

require_once 'import.php';
if($html) {
?>

	<form method="post" enctype="multipart/form-data" action="">
		<p>
            <?php $profilelist=$conf->getProfileList();
                  $profile = isset($_POST['profile']) ? $_POST['profile'] : null;
             ?>
            <!--<label>1 - Seleciona el perfil : </label>
			<select name="profile" id="runprofile">
				<option <?php if(null==$profile){?>selected="selected"<?php }?> value="default">Default</option>
				<?php foreach($profilelist as $profilename){?>
				<option <?php if($profilename==$profile){?>selected="selected"<?php }?> value="<?php echo $profilename?>"><?php echo $profilename?></option>
				<?php }?>
			</select>-->
			<p></p>
			1 - <input type="file" name="stock_csv">
			</p>
			<p>
			2 - <input type="submit" name="upload" value="Importar" class="btn btn-blue big">
			</p>
		</p>
	</form>
	
	</div>	
</body>
</html>
<?php
}
