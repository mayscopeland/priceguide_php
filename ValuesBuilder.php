<?php

require_once "PlayersInput.php";
require_once "PlayersOutput.php";

class ValuesBuilder
{
   public function buildValues(&$players, $input)
   {
      $output = new PlayersOutput();
      $output->components = $this->buildComponentStats($players, $input->categories);
      $isSettled = false;

      do
      {

         // Step 1: Standard Scores
         $this->buildXStats($players, $input->categories, $input->numberOfPlayersDrafted);
         $output->xStatAverages[$output->numberOfIterations] = $this->xStatAverages;
         $output->stdDevs[$output->numberOfIterations] = $this->buildStandardDeviations($players, $input->categories, $input->numberOfPlayersDrafted);
         $output->averages[$output->numberOfIterations] = $this->buildAverages($players, $input->categories, $input->numberOfPlayersDrafted);
         $this->buildStandardScores($players, $input->categories, $output->stdDevs[$output->numberOfIterations], $output->averages[$output->numberOfIterations]);
         usort($players,"sortByTotal");

         // Step 2: Adjust for Replacement
         $output->replacementLevels[$output->numberOfIterations] = $this->adjustForReplacementLevel($players, $input->positions);

         usort($players, "sortByAdjTotal");

         for ($i = 0; $i < $output->numberOfIterations; $i++)
         {
            if (!array_diff($output->stdDevs[$output->numberOfIterations], $output->stdDevs[$i]))
            {
               $isSettled = true;
            }
         }

         $output->totalValue = $this->buildTotalValue($players, $input->numberOfPlayersDrafted);
         $output->numberOfIterations++;
      }
      while (!$isSettled);

      return $output;
   }

   public function buildPointsValues(&$players, $input)
   {
      $output = new PlayersOutput();

      // Step 1: Add up values
      foreach ($input->categories as $category => $value)
      {
         foreach ($players as &$player)
         {
            $player["total"] += $player[$category] * $value;
         }
      }
      usort($players, "sortByTotal");

      // Step 2: Adjust for Replacement
      $output->replacementLevels[$output->numberOfIterations] = $this->adjustForReplacementLevel($players, $input->positions);

      usort($players, "sortByAdjTotal");

      for ($i = 0; $i < $output->numberOfIterations; $i++)
      {
         if (!array_diff($output->stdDevs[$output->numberOfIterations], $output->stdDevs[$i]))
         {
            $isSettled = true;
         }
      }

      $output->totalValue = $this->buildTotalValue($players, $input->numberOfPlayersDrafted);
      $output->numberOfIterations++;

      return $output;
   }

   private $xStatAverages = array();

   private function buildComponentStats(&$players, $categories)
   {
      $components = array();

      foreach ($categories as $category)
      {
         switch ($category)
         {
            // Hitting categories
            case "1B":
               $this->build1B($players);
               $components = array_merge($components, array("1B"));
               break;
            case "2B+3B":
               $this->build2B3B($players);
               $components = array_merge($components, array("2B", "3B"));
               break;
            case "TB":
               $this->build1B($players);
               $this->buildTB($players);
               $components = array_merge($components, array("1B", "2B", "3B", "HR"));
               break;
            case "SB-CS":
               $this->buildSBCS($players);
               $components = array_merge($components, array("SB", "CS"));
               break;
            case "xAVG":
               $components = array_merge($components, array("H", "AB"));
               $this->buildRateStat("AVG", $players, array("H"), array("AB"), false);
               break;
            case "xOBP":
               $this->buildPA($players);
               $components = array_merge($components, array("H", "BB", "HBP", "AB", "SF"));
               $this->buildRateStat("OBP", $players, array("H", "BB", "HBP"), array("PA"), false);
               break;
            case "xSLG":
               $this->build1B($players);
               $this->buildTB($players);
               $components = array_merge($components, array("1B", "2B", "3B", "HR", "AB"));
               $this->buildRateStat("SLG", $players, array("TB"), array("AB"), false);
               break;
            case "xOPS":
               $this->build1B($players);
               $this->buildTB($players);
               $components = array_merge($components, array("1B", "2B", "3B", "HR", "BB", "HBP", "AB", "SF"));
               $this->buildOPS($players);
               break;
            case "xSB%":
               $components = array_merge($components, array("SB", "CS"));
               $this->buildRateStat("SB%", $players, array("SB"), array("SB","CS"), false);
               break;
            case "xKAB":
               $components = array_merge($components, array("SO", "AB"));
               $this->buildRateStat("KAB", $players, array("SO"), array("AB"), false);
               break;
            case "RP":
               $this->buildRP($players);
               $components = array_merge($components, array("R", "RBI", "HR"));
               break;

            // Pitching categories
            case "W-L":
               $this->buildWL($players);
               $components = array_merge($components, array("W", "L"));
               break;
            case "2W-L":
               $this->build2WL($players);
               $components = array_merge($components, array("W", "L"));
               break;
            case "S+HLD":
               $this->buildSHLD($players);
               $components = array_merge($components, array("S", "HLD"));
               break;
            case "K-BB":
               $this->buildKBB($players);
               $components = array_merge($components, array("K", "BB"));
               break;
            case "xBAA":
               $this->buildOuts($players);
               $components = array_merge($components, array("IP", "H"));
               $this->buildRateStat("BAA", $players, array("H"), array("Outs", "H"), true);
               break;
            case "xERA":
               $components = array_merge($components, array("IP", "ER"));
               $this->buildRateStat("ERA", $players, array("ER"), array("IP"), true);
               break;
            case "xWHIP":
               $components = array_merge($components, array("BB", "H", "IP"));
               $this->buildRateStat("WHIP", $players, array("H", "BB"), array("IP"), true);
               break;
            case "xK9":
               $components = array_merge($components, array("K", "IP"));
               $this->buildRateStat("K9", $players, array("K"), array("IP"), false);
               break;
            case "xBB9":
               $components = array_merge($components, array("BB", "IP"));
               $this->buildRateStat("BB9", $players, array("BB"), array("IP"), true);
               break;
            case "xKBB":
               $components = array_merge($components, array("K", "BB"));
               $this->buildRateStat("KBB", $players, array("K"), array("BB"), false);
               break;
            case "xHR9":
               $components = array_merge($components, array("HR", "IP"));
               $this->buildRateStat("HR9", $players, array("HR"), array("IP"), true);
               break;
            case "xW%":
               $components = array_merge($components, array("W", "L"));
               $this->buildRateStat("W%", $players, array("W"), array("W","L"), false);
               break;
            default:
               $components = array_merge($components, array($category));
               break;
         }
      }

      return array_unique($components);
   }

   private function buildXStats(&$players, $categories, $numberOfPlayersDrafted)
   {
      foreach ($categories as $category)
      {
         switch ($category)
         {
            // Hitting categories
            case "xAVG":
               $this->buildXStat("AVG", array("H"), array("AB"), false, $players, $numberOfPlayersDrafted);
               break;
            case "xOBP":
               $this->buildXStat("OBP", array("H", "BB", "HBP"), array("AB", "BB", "HBP", "SF"), false, $players, $numberOfPlayersDrafted);
               break;
            case "xSLG":
               $this->buildXStat("SLG", array("TB"), array("AB"), false, $players, $numberOfPlayersDrafted);
               break;
            case "xOPS":
               $this->buildXOPS($players, $numberOfPlayersDrafted);
               break;
            case "xSB%":
               $this->buildXStat("SB%", array("SB"), array("SB", "CS"), false, $players, $numberOfPlayersDrafted);
               break;
            case "xKAB":
               $this->buildXStat("KAB", array("SO"), array("AB"), true, $players, $numberOfPlayersDrafted);
               break;

            // Pitching categories
            case "xERA":
               $this->buildXStat("ERA", array("ER"), array("IP"), true, $players, $numberOfPlayersDrafted);
               break;
            case "xWHIP":
               $this->buildXStat("WHIP", array("BB", "H"), array("IP"), true, $players, $numberOfPlayersDrafted);
               break;
            case "xK9":
               $this->buildXStat("K9", array("K"), array("IP"), false, $players, $numberOfPlayersDrafted);
               break;
            case "xBB9":
               $this->buildXStat("BB9", array("BB"), array("IP"), true, $players, $numberOfPlayersDrafted);
               break;
            case "xKBB":
               $this->buildXStat("KBB", array("K"), array("BB"), false, $players, $numberOfPlayersDrafted);
               break;
            case "xHR9":
               $this->buildXStat("HR9", array("HR"), array("IP"), true, $players, $numberOfPlayersDrafted);
               break;
            case "xW%":
               $this->buildXStat("W%", array("W"), array("W", "L"), false, $players, $numberOfPlayersDrafted);
               break;
            case "xBAA":
               $this->buildXStat("BAA", array("H"), array("H", "Outs"), true, $players, $numberOfPlayersDrafted);
               break;
         }
      }
   }

   private function buildStandardDeviations(&$players, $categories, $numberOfPlayersDrafted)
   {
      $stdDevs = array();

      foreach ($categories as $category)
      {
         $stdDevs[$category] = $this->getSD($this->buildCategoryArray($players, $category, $numberOfPlayersDrafted));
      }

      return $stdDevs;
   }


   private function buildAverages(&$players, $categories, $numberOfPlayersDrafted)
   {
      $averages = array();

      foreach ($categories as $category)
      {
         $averages[$category] = $this->average($this->buildCategoryArray($players, $category, $numberOfPlayersDrafted));
      }

      return $averages;
   }

   private function buildStandardScores(&$players, $categories, $stdDevs, $averages)
   {
      foreach ($players as &$player)
      {
         $player["total"] = 0;

         foreach ($categories as $category)
         {
            if ($stdDevs[$category] == 0)
            {
               $player["m" . $category] = 0;
            }
            elseif ($this->isNegativeCategory($category, $player["IP"]))
            {
               $player["m" . $category] = ($player[$category] - $averages[$category]) / $stdDevs[$category] * -1.0;
               $player["total"] += $player["m". $category];
            }
            else
            {
               $player["m" . $category] = ($player[$category] - $averages[$category]) / $stdDevs[$category];
               $player["total"] += $player["m". $category];
            }
         }
      }
   }

   private function isNegativeCategory($category, $ip)
   {
      if ($category == "E")
      {
         return true;
      }
      if ($category == "GIDP")
      {
         return true;
      }
      if ($category == "SO")
      {
         return true;
      }
      if ($category == "L")
      {
         return true;
      }
      if (($category == "H") && $ip)
      {
         return true;
      }
      if (($category == "BB") && $ip)
      {
         return true;
      }
      if (($category == "HR") && $ip)
      {
         return true;
      }
      
      return false;
   
   }


   private function adjustForOverallReplacementLevel(&$players, $numberOfPlayersDrafted)
   {
      $overallReplacementLevel = $players[$numberOfPlayersDrafted]["total"];
      foreach ($players as &$player)
      {
         $player["adjTotal"] = $player["total"] - $overallReplacementLevel;
      }
   }

   private function adjustForReplacementLevel(&$players, $positions)
   {

      $replacementLevels = $this->buildReplacementLevels($players, $positions);

      foreach ($players as &$player)
      {
         if (count($player["pos"]) > 0)
         {
            $player["adjTotal"] = $player["total"] - $replacementLevels[$player["pos"][0]];
         }
         else
         {
            $maxReplacementLevel = PHP_INT_MAX * -1;
            foreach ($positions as $position => $value)
            {
               if ($replacementLevels[$position] > $maxReplacementLevel)
               {
                  $maxReplacementLevel = $replacementLevels[$position];
               }
            }
            $player["adjTotal"] = $player["total"] - $maxReplacementLevel;
         }
         $player["isAboveReplacement"] = false;
      }

      return $replacementLevels;
   }

   private function buildReplacementLevels(&$players, $positions)
   {
      $replacementLevels = array();
      
      foreach ($players as &$player)
      {
         $player["isAboveReplacement"] = false;
      }

      foreach ($positions as $position => $numPlayersAtPosition)
      {
         if ($numPlayersAtPosition > 0)
         {
            $numPlayersAtPositionFound = 0;
            $subpositionsAtPositionFound = array();
            foreach ($players as &$player)
            {
               if ($this->playerMatchesPos($player, $position))
               {
                  $numPlayersAtPositionFound++;
                  $player["isAboveReplacement"] = true;

                  $replacementLevels[$position] = $player["total"];
                  $subpositionsAtPositionFound[] = $player["pos"][0];
                  
               }

               // Once we find enough players who qualify at the position,  move to the next position.
               if ($numPlayersAtPositionFound == $numPlayersAtPosition)
               {
                  foreach ($subpositionsAtPositionFound as $subposition)
                  {
                     $replacementLevels[$subposition] = $replacementLevels[$position];
                  }
                  break;
               }
            }
         }
      }

      return $replacementLevels;
   }

   private function playerMatchesPos($player, $position)
   {

      if ($player["isAboveReplacement"])
      {
         return false;
      }

      if ($player["pos"][0] == $position)
      {
         return true;
      }

      if ($position == "MI")
      {
         if (is_array($player["pos"]))
         {
            if (count(array_intersect(array("SS", "2B"), $player["pos"])) > 0)
            {
               return true;
            }
         }
         else
         {
            if (($player["pos"] == "SS") || ($player["pos"] == "2B"))
            {
               return true;
            }
         }
      }

      if ($position == "CI")
      {
         if (is_array($player["pos"]))
         {
            if (count(array_intersect(array("3B", "1B"), $player["pos"])) > 0)
            {
               return true;
            }
         }
         else
         {
            if (($player["pos"] == "3B") || ($player["pos"] == "1B"))
            {
               return true;
            }
         }
      }

      if ($position == "IF")
      {
         if (is_array($player["pos"]))
         {
            if (count(array_intersect(array("3B", "1B", "2B", "SS"), $player["pos"])) > 0)
            {
               return true;
            }
         }
         else
         {
            if (($player["pos"] == "3B") || ($player["pos"] == "1B") || ($player["pos"] == "2B") || ($player["pos"] == "SS"))
            {
               return true;
            }
         }
      }

      if ($position == "OF")
      {
         if (is_array($player["pos"]))
         {
            if (count(array_intersect(array("LF", "CF", "RF"), $player["pos"])) > 0)
            {
               return true;
            }
         }
         else
         {
            if (($player["pos"] == "LF") || ($player["pos"] == "CF") || ($player["pos"] == "RF"))
            {
               return true;
            }
         }
      }

      if ($position == "Util")
      {
         return true;
      }

      if ($position == "P")
      {
         return true;
      }

      return false;
   }

   private function buildCategoryArray($players, $category, $numberOfPlayersDrafted)
   {
      $catArray = array();
      for ($i = 0; $i < $numberOfPlayersDrafted; $i++)
      {
         $catArray[$i] = $players[$i][$category];
      }

      return $catArray;
   }

   private function average($array)
   {
      if (is_array($array))
      {
         $sum  = array_sum($array);
      }
      $count = count($array);

      if ($count > 0)
      {
         return $sum / $count;
      }
      else
      {
         return 0;
      }
   }

   private function getSD($array)
   {
      $avg = $this->average($array);

      foreach ($array as $value)
      {
         $variance[] = pow($value-$avg, 2);
      }

      $deviation = sqrt($this->average($variance));

      return $deviation;
   }

   private function build1B(&$players)
   {
      foreach ($players as &$player)
      {
         $player["1B"] = $player["H"] - ($player["2B"] + $player["3B"] + $player["HR"]);
      }
   }

   private function buildTB(&$players)
   {
      foreach ($players as &$player)
      {
         $player["TB"] = $player["1B"] + ($player["2B"] * 2) + ($player["3B"] * 3) + ($player["HR"] * 4);
      }
   }

   private function build2B3B(&$players)
   {
      foreach ($players as &$player)
      {
         $player["2B+3B"] = $player["2B"] + $player["3B"];
      }
   }

   private function buildRP(&$players)
   {
      foreach ($players as &$player)
      {
         $player["RP"] = $player["R"] + $player["RBI"] - $player["HR"];
      }
   }

   private function buildPA(&$players)
   {
      foreach ($players as &$player)
      {
         $player["PA"] = $player["AB"] + $player["BB"] + $player["HBP"] + $player["SF"];
      }
   }

   private function buildSBCS(&$players)
   {
      foreach ($players as &$player)
      {
         $player["SB-CS"] = $player["SB"] - $player["CS"];
      }
   }

   private function buildWL(&$players)
   {
      foreach ($players as &$player)
      {
         $player["W-L"] = $player["W"] - $player["L"];
      }
   }

   private function build2WL(&$players)
   {
      foreach ($players as &$player)
      {
         $player["2W-L"] = ($player["W"] * 2) - $player["L"];
      }
   }

   private function buildKBB(&$players)
   {
      foreach ($players as &$player)
      {
         $player["K-BB"] = $player["K"] - $player["BB"];
      }
   }

   private function buildSHLD(&$players)
   {
      foreach ($players as &$player)
      {
         $player["S+HLD"] = $player["S"] + $player["HLD"];
      }
   }

   private function buildOuts(&$players)
   {
      foreach ($players as &$player)
      {
         $player["Outs"] = $player["IP"] * 3;
      }
   }

   private function buildBAA(&$players)
   {
      foreach ($players as &$player)
      {
         if (($player["IP"] > 0) || ($player["H"] > 0) )
         {
            $player["BAA"] = $player["H"] / ($player["H"] + ($player["IP"] * 3));
         }
         else
         {
            $player["BAA"] = PHP_INT_MAX;
         }
      }
   }

   private function buildRateStat($statName, &$players, $numerator, $denominator, $smallIsGood)
   {
      foreach ($players as &$player)
      {
         $totalNumerator = $this->addArray($player, $numerator);
         $totalDenominator = $this->addArray($player, $denominator);

         if ($totalDenominator > 0)
         {
            $player[$statName] = $totalNumerator / $totalDenominator;
         }
         else
         {
            if ($smallIsGood)
            {
               $player[$statName] = PHP_INT_MAX;
            }
            else
            {
               $player[$statName] = 0;
            }
         }
      }

   }
   
   private function buildOPS(&$players)
   {
      foreach ($players as &$player)
      {
         $OBPNumerator = $this->addArray($player, array("H", "BB", "HBP"));
         $OBPDenominator = $this->addArray($player, array("AB", "BB", "HBP", "SF"));
         $SLGNumerator = $player["TB"];
         $SLGDenominator = $player["AB"];

         if ($SLGDenominator == 0)
         {
            $player["OPS"] = 0;
         }
         else
         {
            $player["OPS"] = ($OBPNumerator / $OBPDenominator) + ($SLGNumerator / $SLGDenominator);
         }
      }
   }

   private function buildXOPS(&$players, $numberOfPlayersDrafted)
   {
      $overallAverage = $this->getAverageOPSOfDraftedPlayers($players, $numberOfPlayersDrafted);
      $this->xStatAverages["OPS"] = $overallAverage;

      foreach ($players as &$player)
      {
         $OBPDenominator = $this->addArray($player, array("AB", "BB", "HBP", "SF"));
         $player["xOPS"] = ($OBPDenominator + $player["AB"]) * ($player["OPS"] - $overallAverage);
      }
   }

   private function buildXStat($statName, $numerator, $denominator, $smallIsGood, &$players, $numberOfPlayersDrafted)
   {
      $overallAverage = $this->getAverageOfDraftedPlayers($numerator, $denominator, $players, $numberOfPlayersDrafted);
      $this->xStatAverages[$statName] = $overallAverage;

      $signConstant = 1;
      if ($smallIsGood)
      {
         $signConstant = -1;
      }

      foreach ($players as &$player)
      {
         $totalNumerator = $this->addArray($player, $numerator);
         $totalDenominator = $this->addArray($player, $denominator);
         $player["x" . $statName] = ($totalNumerator - ( $totalDenominator * $overallAverage )) * $signConstant;
      }
   }

   private function addArray($array, $values)
   {
      $total = 0;

      foreach ($values as $value)
      {
         $total += $array[$value];
      }

      return $total;
   }

   private function getAverageOfDraftedPlayers($numerator, $denominator, $players, $numberOfPlayersDrafted)
   {
      $totalNumerator = 0;
      $totalDenominator = 0;

      for ($i = 0; $i < $numberOfPlayersDrafted; $i++)
      {
         $totalNumerator += $this->addArray($players[$i], $numerator);
         $totalDenominator += $this->addArray($players[$i], $denominator);
      }

      if ($totalDenominator == 0)
      {
         return 0;
      }
      else
      {
         return $totalNumerator / $totalDenominator;
      }
   }

   private function getAverageOPSOfDraftedPlayers($players, $numberOfPlayersDrafted)
   {
      $OBPNumerator = 0;
      $OBPDenominator = 0;
      $SLGNumerator = 0;
      $SLGDenominator = 0;

      for ($i = 0; $i < $numberOfPlayersDrafted; $i++)
      {
         $OBPNumerator += $this->addArray($players[$i], array("H", "BB", "HBP"));
         $OBPDenominator += $this->addArray($players[$i], array("AB", "BB", "HBP", "SF"));
         $SLGNumerator += $players[$i]["TB"];
         $SLGDenominator += $players[$i]["AB"];
      }

      if ($SLGDenominator == 0)
      {
         return 0;
      }
      else
      {
         return ($OBPNumerator / $OBPDenominator) + ($SLGNumerator / $SLGDenominator);
      }
   }

   function buildTotalValue($players, $numberOfPlayersDrafted)
   {
      $totalValue = 0;

      for ($i = 0; $i < $numberOfPlayersDrafted; $i++)
      {
         $totalValue += $players[$i]["adjTotal"];
      }

      return $totalValue;

   }

}


function sortByAdjTotal($first, $second)
{

   if ($first["adjTotal"] == $second["adjTotal"])
   {
      return 0;
   }
   elseif ($first["adjTotal"] < $second["adjTotal"])
   {
      return 1;
   }
   else
   {
      return -1;
   }
}

function sortByTotal($first, $second)
{

   if ($first["total"] == $second["total"])
   {
      return 0;
   }
   elseif ($first["total"] < $second["total"])
   {
      return 1;
   }
   else
   {
      return -1;
   }
}


?>