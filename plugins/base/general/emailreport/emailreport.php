<?php
class EmailReportPlugin extends Magmi_GeneralImportPlugin
{
	protected $_attach;

	public function initialize($params)
	{
		$this->_attach=array();
	}

public function getPluginInfo()
	{
		return array(
            "name" => "Import Report Mail Notifier",
            "author" => "Dweeves",
            "version" => "1.0.0",
			"url"=>"http://sourceforge.net/apps/mediawiki/magmi/index.php?title=Import_report_mail_notifier"
            );
	}
	public function send_email($to, $from, $from_name, $subject, $message, $attachments=false)
	{
		$headers = "From: ".$from_name."<".$from.">\n";
		$headers .= "Reply-To: ".$from_name."<".$from.">\n";
		$headers .= "Return-Path: ".$from_name."<".$from.">\n";
		$headers .= "Message-ID: <".time()."-".$from.">\n";
		$headers .= "X-Mailer: PHP v".phpversion();

		$msg_txt="";
		$email_txt = $message;

		$semi_rand = md5(time());
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

		$headers .= "\nMIME-Version: 1.0\n" .
"Content-Type: multipart/mixed;\n" .
" boundary=\"{$mime_boundary}\"";

		$email_txt .= $msg_txt;
		$email_message = $email_txt;
		$email_message .= "This is a multi-part message in MIME format.\n\n" .
"--{$mime_boundary}\n" .
"Content-Type:text/html; charset=\"iso-8859-1\"\n" .
"Content-Transfer-Encoding: 7bit\n\n" .
		$email_txt . "\n\n";

        $attachments=$this->_attach;

		if ($attachments !== false)
		{
			for($i=0; $i < count($attachments); $i++)
            {
				if (is_file($attachments[$i]))
				{
					$fileatt = $attachments[$i];
					$fileatt_type = "application/octet-stream";
					$start= strrpos($attachments[$i], '/') == -1 ? strrpos($attachments[$i], '//') : strrpos($attachments[$i], '/')+1;
					$fileatt_name = substr($attachments[$i], $start, strlen($attachments[$i]));

					$file = fopen($fileatt,'rb');
					$data = fread($file,filesize($fileatt));
					fclose($file);

					$data = chunk_split(base64_encode($data));

					$email_message .= "--{$mime_boundary}\n" .
"Content-Type: {$fileatt_type};\n" .
" name=\"{$fileatt_name}\"\n" .
"Content-Transfer-Encoding: base64\n\n" .
					$data . "\n\n";

				}
			}
		}

        $email_message .= "--{$mime_boundary}--\n";
        //Accessing from web
        if(isset($_SERVER['SERVER_NAME'])) {
            $website = $_SERVER['SERVER_NAME'];
        } else {
            //Accesing from phpcli, try to retrieve the site url
            $host = explode('/', $_SERVER['PWD']);
            $website = array_filter($host, function($url){
                return preg_match('/^(.*)\.(net|com|es|pt|it)$/', $url);
            });
            if(empty($website)) {
                $website = $host[count($host) -2];
            } else {
                $website = reset($website);
            }
        }
        $pos = strpos($website, 'www');
        $env = "PROD";
        if($pos === FALSE) {
            $pos = strpos($website, 'dev');
            if($pos !== FALSE) {
                $env = "DEV";
            } else {
                $env = "PRE";
            }
        }
        $domain = explode('.', substr($website, $pos !== FALSE ? $pos + 4 : 0));
        $subject = "[" . $env . "][" . $domain[0] . "] " . $subject;
        $this->log("Sending report to : $to ($subject)","info");
        $ok= mail($to, $subject, $email_message, $headers);
		return $ok;
	}

	public function addAttachment($fname)
	{
		$this->_attach[]=$fname;
		$this->_attach=array_unique($this->_attach);
	}

	public function getPluginParams($params)
	{
		$pp=array();
		foreach($params as $k=>$v)
		{
			if(preg_match("/^EMAILREP:.*$/",$k))
			{
				$pp[$k]=$v;
			}
		}
		return $pp;
	}

	public function afterImport()
	{
		$eng=$this->_callers[0];
		if($this->getParam("EMAILREP:to","")!="" && $this->getParam("EMAILREP:from","")!="")
		{
			if($this->getParam("EMAILREP:attachcsv",false)==true)
			{
				$ds=$eng->getPluginInstanceByClassName("datasources","Magmi_CSVDataSource");
				if($ds!=null)
				{
					$csvfile=$ds->getParam("CSV:filename");
					$this->addAttachment($csvfile);
				}
			}

			if($this->getParam("EMAILREP:attachlog",false)==true)
			{
				//copy magmi report
				$pfile=Magmi_StateManager::getProgressFile(true);
				$this->addAttachment($pfile);
			}

			$ok=$this->send_email($this->getParam("EMAILREP:to"),
			$this->getParam("EMAILREP:from"),
			$this->getParam("EMAILREP:from_alias",""),
			$this->getParam("EMAILREP:subject","Magmi import report"),
			$this->getParam("EMAILREP:body","report attached"),$this->_attach);
			if(!$ok)
			{
				$this->log("Cannot send email","error");
			}
		}
	}

}
