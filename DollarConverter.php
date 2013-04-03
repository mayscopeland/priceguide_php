<?php

require_once "PlayersInput.php";
require_once "PlayersOutput.php";
require_once "Request.php";
require_once "Results.php";

class DollarConverter
{

   public function createDollarValues(&$players, $scaleFactor, $minimumBid)
   {
      foreach ($players as &$player)
      {
         $player["dollarValue"] = ($player["adjTotal"] * $scaleFactor) + $minimumBid;
      }
   }

   public function createCategoryDollarValues(&$players, $categories, $scaleFactor, $maxReplacementLevel, $minimumBid)
   {
      foreach ($players as &$player)
      {
         foreach ($categories as $category)
         {
            $player[$category . "$"] = $player["m" . $category] * $scaleFactor;
         }
         $player["Pos$"] = ($player["adjTotal"] - $player["total"] + $maxReplacementLevel) * $scaleFactor;
      }
   }

   public function adjustForInflation(&$hitters, &$pitchers, $marginalMoney)
   {
      $keeperValues = 0;
      $keeperPrices = 0;
      $useKeepers = false;

      foreach ($hitters as &$hitter)
      {
         if ($hitter["keeperPrice"])
         {
            $hitter["keeperPrice"] = intval(preg_replace("/[$, ]/","",$hitter["keeperPrice"]));
            $useKeepers = true;
            $keeperValues += $hitter["dollarValue"];
            $keeperPrices += $hitter["keeperPrice"];
         }
      }

      foreach ($pitchers as $pitcher)
      {
         if ($pitcher["keeperPrice"])
         {
            $pitcher["keeperPrice"] = intval(preg_replace("/[$, ]/","",$pitcher["keeperPrice"]));
            $useKeepers = true;
            $keeperValues += $pitcher["dollarValue"];
            $keeperPrices += $pitcher["keeperPrice"];
         }
      }

      if ($useKeepers)
      {

         $valueAvailableAtDraft = $marginalMoney - $keeperValues;
         $moneyAvailableAtDraft = $marginalMoney - $keeperPrices;

         $inflationRate = $moneyAvailableAtDraft / $valueAvailableAtDraft;

         foreach ($hitters as &$hitter)
         {
            $hitter["adjustedDollarValue"] = $hitter["dollarValue"] * $inflationRate;
         }

         foreach ($pitchers as &$pitcher)
         {
            $pitcher["adjustedDollarValue"] = $pitcher["dollarValue"] * $inflationRate;
         }

         return $inflationRate - 1;
      }
      else
      {
         return 0;
      }
   }

   private function adjustForCustomSplitInflation(&$hitters, &$pitchers, $marginalHitterMoney, $marginalPitcherMoney)
   {
      $keeperValues = 0;
      $keeperPrices = 0;
      $useKeepers = false;

      foreach ($hitters as &$hitter)
      {
         if ($hitter["keeperPrice"])
         {
            $hitter["keeperPrice"] = intval(preg_replace("/[$, ]/","",$hitter["keeperPrice"]));
            $useKeepers = true;
            $keeperValues += $hitter["dollarValue"];
            $keeperPrices += $hitter["keeperPrice"];
         }
      }

      foreach ($pitchers as $pitcher)
      {
         if ($pitcher["keeperPrice"])
         {
            $pitcher["keeperPrice"] = intval(preg_replace("/[$, ]/","",$pitcher["keeperPrice"]));
            $useKeepers = true;
            $keeperValues += $pitcher["dollarValue"];
            $keeperPrices += $pitcher["keeperPrice"];
         }
      }

      if ($useKeepers)
      {

         $valueAvailableAtDraft = $marginalMoney - $keeperValues;
         $moneyAvailableAtDraft = $marginalMoney - $keeperPrices;

         $inflationRate = $moneyAvailableAtDraft / $valueAvailableAtDraft;

         foreach ($hitters as &$hitter)
         {
            $hitter["adjustedDollarValue"] = $hitter["dollarValue"] * $inflationRate;
         }

         foreach ($pitchers as &$pitcher)
         {
            $pitcher["adjustedDollarValue"] = $pitcher["dollarValue"] * $inflationRate;
         }

         return $inflationRate - 1;
      }
      else
      {
         return 0;
      }
   }

}


?>