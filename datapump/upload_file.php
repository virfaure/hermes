<?php

if( isset($_POST['upload']) )
{
    $content_dir = 'upload/';
    $tmp_file = $_FILES['stock_csv']['tmp_name'];

    if( !is_uploaded_file($tmp_file) )
    {
        exit("Error al subir el archivo");
    }

	// TYPE
    $type_file = $_FILES['fichier']['type'];
    if( !strstr($type_file, 'csv') )
    {
        exit("El archivo no es un CSV.");
    }

    $name_file = $_FILES['stock_csv']['name'];

    if( !move_uploaded_file($tmp_file, $content_dir . $name_file) )
    {
        exit("Se ha producido un error a subir el archivo.");
    }

    echo "El archivo subio con exito";
	
	//READ FILE
	$row = 1;
	if (($handle = fopen("test.csv", "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$num = count($data);
			echo "<p> $num champs à la ligne $row: <br /></p>\n";
			$row++;
			for ($c=0; $c < $num; $c++) {
				echo $data[$c] . "<br />\n";
			}
		}
		fclose($handle);
	}

}

?>
