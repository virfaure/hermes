<?php

require_once 'init.php';

$columnsMandatory = array('category_id');
$profile = 'import_category';

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
    <title>Creación de Categorías</title>
    <link rel="stylesheet" href="styles/styles.css" type="text/css">
</head>
<body>
	<div id="container">
    <h1>Creación de categorías</h1>
    <div class="explanation">
		<p>Por favor, antes de empezar a subir su fichero, usted tiene que validar que sea un archivo <strong>CSV delimitado por coma</strong> con el formato de <strong>codificación UTF-8.</strong></p>
		<p><strong>Si necesita ayuda, por favor consulte el manual : <a target="_blank" href="<?php echo $manual_url; ?>">Consultar el manual</a></strong> <strong>o consulte</strong> <a href="faq.php" target="_blank">las preguntas frecuentes</a></p>
		<p>Le aconsejamos usar <strong>esta plantilla del fichero de actualización</strong>, la cual le servirá como ejemplo en caso de que no esté seguro de cómo rellenar los campos : <a href="update_category.csv">Descargar la plantilla</a></p>
		<div class="warning">Una vez modificado la plantilla, tiene que guardarlo al formato CSV : Guardar Como -> CSV (delimitado por coma) con el formato de codificación UTF-8. </div>
    </div>
    <hr />

<?php
}
require_once 'importc.php';

if($html) {
?>


    <form method="post" enctype="multipart/form-data" action="">
			<p>
				1 - <input type="file" name="stock_csv">
			</p>
			<p>
				2 - <input type="submit" name="upload" value="Actualizar" class="btn btn-blue big">
			</p>
    </form>

</div>
</body>
</html>
<?php
}
