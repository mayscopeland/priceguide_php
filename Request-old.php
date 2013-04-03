<?php

class Request
{
   public $hittersInput;
   public $pitchersInput;
   public $numberOfPlayersDrafted = 0;
   public $dataset = "10M";
   public $customId = "";
   public $keeperId = "";
   public $numberOfTeams = 12;
   public $moneyPerTeam = 260;
   public $minimumBid = 1;
   public $league = "MLB";
   public $useReliabilityScores = true;
   public $useTopPosition = false;
   public $useCustomSplit = false;
   public $outputAsCSV = false;
   public $outputAsSimpleCSV = false;
   public $hittersSplit = 70;
   public $pitchersSplit = 30;

   function __construct()
   {
       $hittersInput = new PlayersInput();
       $pitchersInput = new PlayersInput();
   }
}

?>