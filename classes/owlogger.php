<?php
class owLogger
{
    function owLogger( $fileName, $mode )
    {
        $this->file = fopen( $fileName, $mode );
        $this->fileName = $fileName;
    }

    static function CreateNew($fileName)
    {
        return new owLogger( $fileName, "wt" );
    }

    static function CreateForAdd($fileName)
    {
        return new owLogger( $fileName, "a+t" );
    }

	static function getTime()
    {
        $time = strftime( "%d-%m-%Y %H-%M-%S" );
        return $time;
    }
    
    function writeString( $string, $label='' )
    {
        if( $this->file )
        {
            if ( is_object( $string ) || is_array( $string ) )
                $string = eZDebug::dumpVariable( $string );

            if( $label == '' )
                fputs( $this->file, $string."\r\n" );
            else
                fputs( $this->file, $label . ': ' . $string."\r\n" );
        }
    }

    function writeTimedString( $string, $label='' )
    {
        if( $this->file )
        {
            $time = $this->getTime();
            $this->setCurrentStartTime($time);

            if ( is_object( $string ) || is_array( $string ) )
                $string = eZDebug::dumpVariable( $string );

            if( $label == '' )
                fputs( $this->file, $time. '  '. $string. "\n" );
            else
                fputs( $this->file, $time. '  '. $label. ': '. $string. "\n" );
        }
    }

    
	function getLogContentFromCurrentStartTime($withTimeKey = false)
	{
		if( $this->fileName )
		{
			$fileContent = file_get_contents($this->fileName);

			$pos = strpos($fileContent,$this->currentStartTime);
			
			$lastLogContent = substr($fileContent,$pos);
			
			//return $lastLogContent;
			
			$datepart = substr($this->currentStartTime,0,10); //var_dump($datepart);
			$logArray = explode($datepart, $lastLogContent);
			
			$logTimedArray = array();
			if($withTimeKey)
			{
				foreach($logArray as $value)
				{
					$timepart = substr($value,0,9);
					$key = $datepart . $timepart;
					while( array_key_exists($key,$logTimedArray) )
					{
						$seconds = substr($timepart,-2); 
						$seconds = ltrim($seconds, '0');
						$seconds = $seconds + 1;
						$timepart = substr($timepart,0,-2) . $seconds;
						$key = $datepart . $timepart;
					}
					
					$logTimedArray[$key] = substr($value,9);
				}
			}
			else
			{
				foreach($logArray as $value)
				{
					$logTimedArray[] = $datepart . $value;
				}
			}	
			
			return $logTimedArray;
			
		}
	}
	
	protected function setCurrentStartTime($time)
	{
		if(!isset($this->currentStartTime))
			$this->currentStartTime = $time;
	}
    
    
    public $file;
    public $fileName;
    public $currentStartTime;
}
?>