<?php

class Results
{
   public $hittersOutput;
   public $pitchersOutput;
   public $hitters = array();
   public $pitchers = array();
   public $totalIP = 0;
   public $inflationRate = 0.00;

   function __construct()
   {
       $hittersOutput = new PlayersOutput();
       $pitchersOutput = new PlayersOutput();
   }
}

?>