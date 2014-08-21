<?php
$columnsMandatory = array('sku');
$profile = 'super';
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
    <title>Actualizacion de Productos</title>
    <style>
        body{ font: 12px/1.35 Arial,Helvetica,sans-serif;}
        ul{list-style: none outside none;}
        div.explanation{font-size:11px; padding:10px; color:#2D282E;}
        form{width:50%; padding:5px;}
        pre {
            white-space: pre-wrap;
        }
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
    <h1>Sube tu fichero csv de actualización de productos</h1>
    <div class="explanation">
        El archivo de actualización de productos es un csv con un punto y coma (;) como caracter de separacion, y varias columnas como :
        <ul>
            <li>sku, qty, price, image, ean, categories : <br />
            <i>to activate this mode, you MUST set category_reset to 0. <br />
            sku,....,category_ids <br />
            00001,....,"-7,-12,27" <= remove sku 00001 from categories 7,12 , add it to 27 (23 will be kept, since relative assignment detected in syntax) <br />
            00002,....,"11" <= put sku 00002 in category 11 , remove all other assigment (no relative syntax used) <br />
            00003,....,"-11,+9" <= remove sku 00003 from category 11, add it to category 9.</i> </li>
        </ul>
        Si necesitas ayuda, puedes enviar un mail a <a href="mailto:tecnico@theetailers.com">tecnico@theetailers.com</a>.<br /><br />
        Aqui tienes un ejemplo de un fichero vacio : <a href="update_product.xls">Descargar el ejemplo de xls</a>
        <div style="color:#ce0000; font-weight:bold;">Una vez modificado el fichero xls de ejemplo, tienes que guardarlo al formato CSV : Guardar Como -> CSV (delimitado por punto y coma). </div>
    </div>


<?php
}   
require_once 'import.php';
if($html) {
?>


    <form method="post" enctype="multipart/form-data" action="">
        <p>
            <input type="file" name="stock_csv">
            <input type="submit" name="upload" value="Subir">
        </p>
    </form>

</body>
</html>
<?php
}
?>
