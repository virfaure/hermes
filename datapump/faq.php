<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
<head>
    <meta charset='utf-8'>
    <title>Preguntas frecuentes de Hermes</title>
    <link rel="stylesheet" href="styles/styles.css" type="text/css">
    <link rel="stylesheet" href="styles/fancybox/jquery.fancybox.css" type="text/css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/jquery-ui.min.js"></script>
    <script src="js/fancybox/jquery.fancybox.js"></script>

    <script type="text/javascript">
		$(document).ready(function() {
			$("#accordion > dd:last").addClass("last");
			$("#accordion > dt:last").addClass("last");
			$( "#accordion" ).accordion();

			$(".fancybox").attr('rel', 'gallery').fancybox({padding : 0});
		});
    </script>

</head>
<body>
	<div id="container">

		<br><br>
		<h1>Preguntas frecuentes de Hermes</h1>

		<p>
			<strong>Si necesita ayuda, por favor consulte el manual de importación : <a href="../docs/Manual_Clientes_Hermes.pdf">Descargar el manual.</a></strong>
			<br /><br />
			Si no tiene Excel, puede descargar <a href="http://es.libreoffice.org/descarga/" target="_blank">Libre Office</a>, una suite ofimática libre y gratuita. <sup><a class="sup" href="http://es.wikipedia.org/wiki/LibreOffice" target="_blank">Enlace Wikipedia</a></sup>
		</p>

		<dl id="accordion" class="faq">

			<dt><a href="">No puedo subir el fichero CSV y me da errores</a></dt>
			<dd>
				Hermes acepta solo los archivos <strong>CSV delimitado por coma</strong> con el formato de <strong>codificación UTF-8.</strong> <sup><a class="sup" href="http://es.wikipedia.org/wiki/UTF8" target="_blank">Enlace Wikipedia</a></sup>
				<br />
				Por favor, revise que el formato de su fichera este correcto, tal y como esta indicado en <strong>el manual</strong> y intentelo otra vez.
				<br />
				<ul>
					<li><a href="images/saveas.jpg" rel="excel-cod" class="fancybox">1 - Guardar Como CSV</a></li>
					<li><a href="images/codification.jpg" rel="excel-cod" class="fancybox">2 - Cambiar la codificación</a></li>
					<li><a href="http://www.youtube.com/watch?v=ED5RAA6C3Ns" target="_blank">Video Tutorial para Guardar un Excel como .CSV</a></li>
				</ul>
			</dd>

			<dt><a href="">No se ven las imagenes del producto</a></dt>
			<dd>
                Por favor, compruebe los siguientes puntos : 
                <br />
                <ul>
					<li>No se permite accentos, ñ, caracteres raros o espacios en los nombres de las imagenes. Solo caracteres alfanumericos (a->z, 0->9), guillon bajo(_) y guillon medio(-).</li>
                    <li>Los nombres de las imagenes en el FTP deben corresponder EXACTAMENTE al nombre del las imagenes en el CSV, y sobre todo que coinciden los mayusculas/minusculas.</li>
                    <li>Las imagenes deben tener la extension especifica. Una imagen sín extension valída será rechazada.</li>

			</dd>

			<dt><a href="">¿No ha encontrado solucíon a su problema?</a></dt>
			<dd>Por favor, póngase en contacto con nosotros enviando un mail a <a href="mailto:tecnico@theetailers.com?subject=Problema Hermes">tecnico@theetailers.com</a> con su fichero en adjunto.</dd>

		</dl>

	</div>
</body>
</html>
