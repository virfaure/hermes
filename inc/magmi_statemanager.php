<?php
if(!defined("DS"))
{
	define("DS",DIRECTORY_SEPARATOR);
}
class Magmi_StateManager
{
	private static $_statefile=null;
	private static $_script=__FILE__;
    private static $_state="idle";
    private static $_progressFile = null;


	public static function getStateFile()
	{
		return self::getStateDir().DS."magmistate";
	}

	public static function getTraceFile()
	{
		return self::getStateDir().DS."trace.txt";

	}

	public static function getStateDir()
	{
		return dirname(dirname(self::$_script)).DS."state";
	}

    /**
     * getProgressFile
     * Added timestamp to avoid overriding the same file.
     *
     * @param mixed $full
     * @static
     * @access public
     * @return string
     */
    public static function getProgressFile($full=false)
    {
        if(!self::$_progressFile) {
            self::$_progressFile = HermesHelper::getProject() . '.' . str_replace('.', '', microtime(true)) . '.txt';
        }
            $fullname=self::getStateDir().DS. self::$_progressFile;
            $pfname=($full?$fullname:self::$_progressFile);
            return $pfname;
	}

	public static function setState($state,$force=false)
	{

		if(self::$_state==$state && !$force)
		{
			return;
		}

		self::$_state=$state;
		$f=fopen(self::getStateFile(),"w");
		fwrite($f,self::$_state);
		fclose($f);
		@chmod(self::getStateFile(),0664);
		if($state=="running")
		{
			$f=fopen(self::getTraceFile(),"w");
			fclose($f);
			@chmod(self::getTraceFile(),0664);
		}
	}

	public static function getState($cached=false)
	{
		if(!$cached)
		{
			if(!file_exists(self::getStateFile()))
			{
				self::setState("idle",true);
			}
			$state=file_get_contents(self::getStateFile());
		}
		else
		{
			$state=self::$_state;
		}
		return $state;
	}

}
