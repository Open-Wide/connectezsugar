<?php

class SmartCLI extends eZCLI
{
	
	/**
     * Returns a shared instance of the SmartCLI class.
     *
     * @return SmartCLI
     */
    static function instance()
    {
        if ( !isset( $GLOBALS['eZCLIInstance'] ) ||
             !( $GLOBALS['eZCLIInstance'] instanceof SmartCLI ) )
        {
            $GLOBALS['eZCLIInstance'] = new SmartCLI();
        }

        $GLOBALS['eZCLIInstance']->setUseStyles( true ); // enable colors
        
        return $GLOBALS['eZCLIInstance'];
    }
	
	/*!
     \return the text \a $text wrapped in the style \a $styleName.
    */
    public function instylize( $instyle, $text, $outstyle )
    {
    	
        $preStyle = $this->style( $outstyle . '-end' ) . $this->style( $instyle );
        $postStyle = $this->style( $instyle . '-end' ) . $this->style( $outstyle );
        return $preStyle . $text . $postStyle;
    }
    
	/*
     * fait un output du message $message avec la coleur $color
     * @param $message string
     * @param $params array('color' => string, 'addEOL' => boolean(true), 'indent' => integer, 'emptyline' => boolean(false) )
    */
	public function styleout( $message, $params = array() )
	{
		if( count($params) > 0 )
		{
			// si definie applique le style $params['color'] à $message 
			if( isset($params['color']) and is_string($params['color']) )
				$message = $this->stylize($params['color'],$message);
				
			// si definie rajoute $params['indent'] tabs devant $message 
			if( isset($params['indent']) and is_integer($params['indent']) and $params['indent'] < 10 )
				$message = $this->indent($message, $params['indent']);
				
			// print du message
			$this->output($message);
				
			// si definie rajoute ou pas EOL à la fin du $message
			// par default la rajoute
			if( !isset($params['addEOL']) or  $params['addEOL'] !== false )
				$addEOL = true;
			else
				$addEOL = false;
				
			// si definie rajoute une ligne vide après la fin du $message
			if( isset($params['emptyline']) and $params['emptyline'] === true )
				$this->emptyline();
		}
		else
			$this->output($message);
	}
    
    /*
     * fait un output du message $message avec la coleur $color
     * @param $color string
     * @param $message string
    */
	public function colorout($color, $message, $indent=0, $emptyline=false)
	{
		// applique le style $color à $message
		$message = $this->stylize($color,$message);
		// rajoute $indent indentations 
		$this->output($this->indent($message, $indent));
		// si $emptyline est à TRUE rajoute une ligne vide
		if($emptyline)
			$this->emptyline();
	}
	
	/*
	 * fais reference à eZCLI::notice
	 * pour SmartCLI les message de type gnotice seront en green
	 */
	public function gnotice( $message = false, $params = array() )
    {
		if ( $this->isQuiet() )
            return;
            
    	$params['color'] = "green";
        $this->styleout($message, $params);
    }
    
	/*
	 * fais reference à eZCLI::notice
	 * pour SmartCLI les message de type dgnotice seront en dark-green
	 */
	public function dgnotice( $message = false, $params = array() )
    {
        if ( $this->isQuiet() )
            return;
		
    	$params['color'] = "dark-green";
        $this->styleout($message, $params);
    }
    
	/*
	 * fais reference à eZCLI::notice
	 * pour SmartCLI les message de type dynotice seront en dark-yellow
	 */
	public function dynotice( $message = false, $params = array() )
    {
        if ( $this->isQuiet() )
            return;
		
    	$params['color'] = "dark-yellow";
        $this->styleout($message, $params);
    }
    
	
    public function beginout($scriptname)
    {
    	$params = array('color' => "green-bg", 'emptyline' => true);
    	$this->styleout("Demarrage du script " . $scriptname, $params);
    }
    
	public function endout($scriptname)
    {
    	$this->emptyline();
    	$params = array('color' => "green-bg");
    	$this->styleout("Fin du script " . $scriptname, $params);
    }
    
	// fonction pour afficher les variable en console
	public function show($var)
	{
		$show = print_r($var,true);
		return $show; 
	}
	
	// fonction pour indenter du text
	public function indent($text, $tabs=1)
	{
		for($i=0;$i<$tabs;$i++)
		{
			$text = "	" . $text;
		}
		
		return $text; 
	}
	
	public function color($color,$string)
	{
		$colored = $this->stylize($color,$string);
		
		return $colored;
	}
	
	public function incolor($incolor, $string, $outcolor)
	{
		$colored = $this->instylize($incolor, $string, $outcolor);
		
		return $colored;
	}
	
	public function emptyline()
	{
		$this->output("\n");
	}
	
	public function title($texte)
	{
		$this->emptyline();
		$this->gnotice("********************************************");
		$this->gnotice($texte);
		$this->gnotice("********************************************");
		$this->emptyline();
	}

	
} // END of CLASS

?>