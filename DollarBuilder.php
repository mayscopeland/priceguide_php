<?php

class DollarBuilder
{

   public function buildDollars($request, &$results)
   {
      $totalDraftMoney = $request->moneyPerTeam * $request->numberOfTeams;
      if (!$request->useCustomSplit)
      {
         $totalValue = $results->hittersOutput->totalValue + $results->pitchersOutput->totalValue;
         $marginalMoney = $totalDraftMoney - ( $request->minimumBid * ( $request->hittersInput->numberOfPlayersDrafted + $request->pitchersInput->numberOfPlayersDrafted ));

         if ($totalValue > 0)
         {
            $scaleFactor = $marginalMoney / $totalValue;
         }
         else
         {
            $scaleFactor = 0;
         }

         $this->createDollarValues($results->hitters, $scaleFactor, $request->minimumBid);
         $this->createDollarValues($results->pitchers, $scaleFactor, $request->minimumBid);

         $this->createCategoryDollarValues($results->hitters, $request->hittersInput->categories, $scaleFactor, $request->minimumBid);
         $this->createCategoryDollarValues($results->pitchers, $request->pitchersInput->categories, $scaleFactor, $request->minimumBid);
      }
      else
      {
         $marginalHitterMoney = $request->hittersSplit * $totalDraftMoney - ( $request->minimumBid * $request->hittersInput->numberOfPlayersDrafted );
         $marginalPitcherMoney = $request->pitchersSplit * $totalDraftMoney - ( $request->minimumBid * $request->pitchersInput->numberOfPlayersDrafted );
         $marginalMoney = $marginalHitterMoney + $marginalPitcherMoney;

         if ($results->hittersOutput->totalValue > 0)
         {
            $hitterScaleFactor = $marginalHitterMoney / $results->hittersOutput->totalValue;
         }
         else
         {
            $hitterScaleFactor = 0;
         }
         if ($results->pitchersOutput->totalValue > 0)
         {
            $pitcherScaleFactor = $marginalPitcherMoney / $results->pitchersOutput->totalValue;
         }
         else
         {
            $pitcherScaleFactor = 0;
         }

         $this->createDollarValues($results->hitters, $hitterScaleFactor, $request->minimumBid);
         $this->createDollarValues($results->pitchers, $pitcherScaleFactor, $request->minimumBid);

         $this->createCategoryDollarValues($results->hitters, $request->hittersInput->categories, $hitterScaleFactor, $request->minimumBid);
         $this->createCategoryDollarValues($results->pitchers, $request->pitchersInput->categories, $pitcherScaleFactor, $request->minimumBid);
      }

      $results->inflationRate = $this->adjustForInflation($results->hitters, $results->pitchers, $marginalMoney);
   }


   private function createDollarValues(&$players, $scaleFactor, $minimumBid)
   {
      foreach ($players as &$player)
      {
         $player["dollarValue"] = ($player["adjTotal"] * $scaleFactor) + $minimumBid;
      }
   }

   /*
   private function createDollarValues(&$players, $totalValue, $marginalMoney, $minimumBid)
   {
      foreach ($players as &$player)
      {
         if ($totalValue > 0)
         {
            $player["dollarValue"] = (($player["adjTotal"] / $totalValue) * $marginalMoney) + $minimumBid;
         }
      }
   }
   */

   private function createCategoryDollarValues(&$players, $categories, $scaleFactor, $minimumBid)
   {
      foreach ($players as &$player)
      {
         foreach ($categories as $category)
         {
            $player[$category . "$"] = $player["m" . $category] * $scaleFactor;
         }
         $player["Pos$"] = ($player["adjTotal"] - $player["total"]) * $scaleFactor;
      }
   }

   private function adjustForInflation(&$hitters, &$pitchers, $marginalMoney)
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
