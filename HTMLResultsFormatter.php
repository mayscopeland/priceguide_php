<?php

require_once "Request.php";
require_once "Results.php";

require_once "PlayersInput.php";
require_once "PlayersOutput.php";

class HTMLResultsFormatter
{
   private $displayStatsOnly = false;
   private $pointsLeague = false;
   
   private $categoryMins = array();
   private $categoryMaxes = array();
   private $categoryMidpoints = array();

   public function buildHTML($request, $results, $numberOfPlayersDisplayed, $queryString, $displayStatsOnly, $pointsLeague, $debugMode)
   {
      $this->displayStatsOnly = $displayStatsOnly;
      $this->pointsLeague = $pointsLeague;

      if (!$this->displayStatsOnly)
      {
         $this->printAdHeader($queryString);
      }

      print "<div id='stats'>";
      $this->printTabs();
      $this->printPlayers($results->hitters, $results->inflationRate, $results->hittersOutput->components, $request->hittersInput->categories, $request->hittersInput->positions, ($request->customId || $request->keeperId), $request->minimumBid, "hitters", $numberOfPlayersDisplayed, $debugMode);
      $this->printPlayers($results->pitchers, $results->inflationRate, $results->pitchersOutput->components, $request->pitchersInput->categories, $request->pitchersInput->positions, ($request->customId || $request->keeperId), $request->minimumBid, "pitchers", $numberOfPlayersDisplayed, $debugMode);

      if (!$this->displayStatsOnly)
      {
         $this->printLeagueStats($request, $results, $queryString, $debugMode);

         if ($request->customId && $request->keeperId)
         {
            $this->printEditForm($results->hittersOutput->components, $request->hittersInput->categories, $results->inflationRate, $request->customId, "hitters",  $debugMode);
            $this->printEditForm($results->pitchersOutput->components, $request->pitchersInput->categories, $results->inflationRate, $request->customId, "pitchers", $$debugMode);
         }
         elseif ($request->customId)
         {
            $this->printStatEditForm($results->hittersOutput->components, $request->hittersInput->categories, $results->inflationRate, $request->customId, "hitters",  $debugMode);
            $this->printStatEditForm($results->pitchersOutput->components, $request->pitchersInput->categories, $results->inflationRate, $request->customId, "pitchers", $$debugMode);
         }
         elseif ($request->keeperId)
         {
            $this->printKeeperEditForm($results->hittersOutput->components, $request->hittersInput->categories, $results->inflationRate, $request->keeperId, "hitters",  $debugMode);
            $this->printKeeperEditForm($results->pitchersOutput->components, $request->pitchersInput->categories, $results->inflationRate, $request->keeperId, "pitchers", $$debugMode);
         }
      }
      print "</div>";

   }
   
   private function printAdHeader($queryString)
   {
?>

<div class='extra_wide_top'>


<script type="text/javascript"><!--
google_ad_client = "pub-1255354711229682";
/* Price Guide, Top */
google_ad_slot = "6960393350";
google_ad_width = 728;
google_ad_height = 15;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>

Add these dollar values to your fantasy league homepage (requires Firefox + Greasemonkey):<br />
<a href="gmbuild.php?<?php print $queryString ?>&gm=Y&x=.user.js">Yahoo</a> |
<a href="gmbuild.php?<?php print $queryString ?>&gm=E&x=.user.js">ESPN</a> |
<a href="gmbuild.php?<?php print $queryString ?>&gm=C&x=.user.js">CBSSports</a>
</div>

<?
   }

   private function printLeagueStats($request, $results, $queryString, $debugMode)
   {

      print "<div id='info'>";
      print "<table cellspacing='0' width='100%' style='margin-bottom:20px;'><tr><td style='text-align:left;'>";
      print "Iterations to find optimal hitters: " . $results->hittersOutput->numberOfIterations . "<br />";
      print "Iterations to find optimal pitchers: " . $results->pitchersOutput->numberOfIterations . "<br /><br />";
      if ($results->inflationRate)
      {
         print "Inflation rate: " . number_format($results->inflationRate * 100, 2) . "%<br />";
      }
      print "IP per team: " . number_format($results->totalIP, 2) . "<br />";
      $totalValue = $results->hittersOutput->totalValue + $results->pitchersOutput->totalValue;
      if ($totalValue != 0)
      {
         if ($debugMode)
         {
            print "Hitting/Pitching Value: " . number_format($results->hittersOutput->totalValue, 2) . "/" . number_format($results->pitchersOutput->totalValue, 2) . "<br />";
         }
         if (!$request->useCustomSplit)
         {
            print "Hitting/Pitching Split: " . number_format(($results->hittersOutput->totalValue / $totalValue) * 100) . "/" . number_format(($results->pitchersOutput->totalValue / $totalValue) * 100) . "<br />";
         }
         else
         {
            print "Optimal Hitting/Pitching Split: " . number_format(($results->hittersOutput->totalValue / $totalValue) * 100) . "/" . number_format(($results->pitchersOutput->totalValue / $totalValue) * 100) . "<br />";
            print "Actual Hitting/Pitching Split: " . number_format($request->hittersSplit * 100) . "/" . number_format($request->pitchersSplit * 100) . "<br />";
         }
      }
      if (!$this->pointsLeague)
      {
         print "<br /><a href=\"index.php?" . $queryString. "&o=CSV\">Export to CSV</a><br />";
      }
      else
      {
         print "<br /><a href=\"points.php?" . $queryString. "&o=CSV\">Export to CSV</a><br />";
      }
      print "</td></tr></table>";
      print "<div class='content_wide'>";

      if ($debugMode)
      {
         for ($i = 0; $i < $results->hittersOutput->numberOfIterations; $i++)
         {
            $this->printIteration($results->hittersOutput->stdDevs, $results->hittersOutput->averages, $results->hittersOutput->xStatAverages, $results->hittersOutput->replacementLevels, $i);
         }
         for ($i = 0; $i < $results->pitchersOutput->numberOfIterations; $i++)
         {
            $this->printIteration($results->pitchersOutput->stdDevs, $results->pitchersOutput->averages, $results->pitchersOutput->xStatAverages, $results->pitchersOutput->replacementLevels, $i);
         }
      }
      else
      {
         print "<div class='container'>";
         print "<div class='std_dev'>";
         print "<div class='item_head'>Standard Deviations</div>";
         print "<div class='item'>";
         if (!$this->pointsLeague)
         {
            foreach ($results->hittersOutput->stdDevs[$results->hittersOutput->numberOfIterations - 1] as $category => $value)
            {
               print $category . ": " . number_format($value, 2) . "<br />";
            }
            foreach ($results->pitchersOutput->stdDevs[$results->pitchersOutput->numberOfIterations - 1] as $category => $value)
            {
               print $category . ": " . number_format($value, 2) . "<br />";
            }
         }
         print "</div>";
         print "</div>";

         print "<div class='avgs'>";
         print "<div class='item_head'>Averages</div>";
         print "<div class='item'>";
         if (!$this->pointsLeague)
         {
            foreach ($results->hittersOutput->averages[$results->hittersOutput->numberOfIterations - 1] as $category => $value)
            {
               if (substr($category, 0, 1) == "x")
               {
                  $newCat = substr($category, 1);
                  print $newCat . ": " . $this->formatStat($category, $results->hittersOutput->xStatAverages[$results->hittersOutput->numberOfIterations - 1][$newCat]) . "<br />";
               }
               else
               {
                  print $category . ": " . $this->formatStat($category, $value) . "<br />";
               }
            }
            foreach ($results->pitchersOutput->averages[$results->pitchersOutput->numberOfIterations - 1] as $category => $value)
            {
               if (substr($category, 0, 1) == "x")
               {
                  $newCat = substr($category, 1);
                  print $newCat . ": " . $this->formatStat($category, $results->pitchersOutput->xStatAverages[$results->pitchersOutput->numberOfIterations - 1][$newCat]) . "<br />";
               }
               else
               {
                  print $category . ": " . $this->formatStat($category, $value) . "<br />";
               }
            }
         }
         print "</div>";
         print "</div>";
         
         print "<div class='rep_lev'>";
         print "<div class='item_head'>Replacement Levels</div>";
         print "<div class='item'>";
         
         if ($this->pointsLeague)
         {
            $repPrecision = 0;
         }
         else
         {
            $repPrecision = 2;
         }
         foreach ($results->hittersOutput->replacementLevels[$results->hittersOutput->numberOfIterations - 1] as $position => $value)
         {
            print $position . ": " . number_format($value, $repPrecision) . "<br />";
         }
         foreach ($results->pitchersOutput->replacementLevels[$results->pitchersOutput->numberOfIterations - 1] as $position => $value)
         {
            print $position . ": " . number_format($value, $repPrecision) . "<br />";
         }
         print "</div>";
         print "</div>";
         print "</div>";
      }
      print "</div>";
      print "</div>";
   }

   private function printIteration($stdDevs, $averages, $xStatAverages, $replacementLevels, $iteration)
   {
      print "<div class='container'>";

      print "<div class='std_dev'>";
      print "<div class='item_head'>Standard Deviations</div>";
      print "<div class='item'>";
      foreach ($stdDevs[$iteration] as $category => $value)
      {
         print $category . ": " . number_format($value, 5) . "<br />";
      }
      print "</div>";
      print "</div>";

      print "<div class='avgs'>";
      print "<div class='item_head'>Averages</div>";
      print "<div class='item'>";
      foreach ($averages[$iteration] as $category => $value)
      {
         if (substr($category, 0, 1) == "x")
         {
            $newCat = substr($category, 1);
            print $newCat . ": " . number_format($xStatAverages[$iteration][$newCat], 5) . "<br />";
         }
         else
         {
            print $category . ": " . number_format($value, 5) . "<br />";
         }
      }
      print "</div>";
      print "</div>";
      
      print "<div class='rep_lev'>";
      print "<div class='item_head'>Replacement Levels</div>";
      print "<div class='item'>";
      foreach ($replacementLevels[$iteration] as $position => $value)
      {
         print $position . ": " . number_format($value, 5) . "<br />";
      }
      print "</div>";
      print "</div>";

      print "</div>";
   }

   private function printTabs()
   {
      print "<div id='tabs'>";
      print "<span id='hittersTab' class='tab' onclick=\"showTable('hitters')\" >Hitters</span>";
      print "<span id='pitchersTab' class='tab' onclick=\"showTable('pitchers')\" >Pitchers</span>";
      if (!$this->displayStatsOnly)
      {
         print "<span id='infoTab' class='tab' onclick=\"showTable('info')\" >League Info</span>";
      }
      print "</div>";
   }

   private function buildPositionList($player, $positions)
   {
      $posString = "";

      if (!is_array($player["pos"]))
      {
         return "&nbsp;";
      }

      if (count($player["pos"]) == 1)
      {
         return $player["pos"][0];
      }

      foreach ($player["pos"] as $pos)
      {
         if ($pos == "Util")
         {
         }
         elseif ($pos == "P")
         {
         }
         elseif (($pos == "OF") && (in_array("LF", $player["pos"]) || in_array("CF", $player["pos"]) || in_array("RF", $player["pos"])))
         {
         }
         else
         {
            $posString .= $pos . ",";
         }
      }

      return trim($posString, ",");
   }

   private function printPlayers($players, $useKeepers, $components, $categories, $positions, $showEdit, $minimumBid, $playerType, $numberOfPlayersDisplayed, $debugMode)
   {
      if (!$this->displayStatsOnly)
      {
         $this->calculateMinAndMax($players, $categories);
      }

      print "<div id=\"" . $playerType . "\">";
      print "<table cellspacing='0' width='100%'><tr>";
      print "<th>Name</th>";
      print "<th>Team</th>";
      print "<th><select onchange=\"filterRows(this.options[this.selectedIndex].value, this.parentNode.parentNode.parentNode.parentNode)\">";
      print "<option value='All' onselect='filterRows()'>Pos: All</option>";
      foreach ($positions as $position => $numStarters)
      {
         if ($numStarters > 0)
         {
            print "<option value='" . $position . "'>Pos: " . $position . "</option>";
         }
      }
      print "<option value=\" \">Pos: </option></select></th>";
      if (!$this->displayStatsOnly)
      {
         print "<th>$</th>";

         if ($useKeepers)
         {
            print "<th>$ (Inf)</th>";
            print "<th>Kept</th>";
         }
         // Show category dollar values
         if (!$this->pointsLeague)
         {
            foreach ($categories as $category)
            {
               if ($category[0] == "x")
               {
                  print "<th>" . substr($category, 1) . "$" . "</th>";
               }
               else
               {
                  print "<th>" . $category . "$" . "</th>";
               }
            }
            print "<th>Pos$</th>";
         }
      }

      // Show raw category stats
      if (!$this->pointsLeague)
      {
         foreach ($categories as $category)
         {
            if ($category[0] == "x")
            {
               print "<th>" . substr($category, 1) . "</th>";
            }
            else
            {
               print "<th>" . $category . "</th>";
            }
            if ($debugMode)
            {
               print "<th bgcolor='#cccccc'>" . "m" . $category . "</th>";
            }
         }
      }
      else
      {
         foreach ($categories as $category => $value)
         {
            print "<th>" . $category . "</th>";
         }
      }

      if (!$this->displayStatsOnly)
      {
         foreach ($components as $key => $component)
         {
            if (!in_array($component, $categories))
            {
               print "<th>" . $component . "</th>";
            }
         }

         // Show totals column
         if ($debugMode || $this->pointsLeague)
         {
            print "<th>Total</th>";
            print "<th>Adj. Total</th>";
         }

         // Show edit button column
         if ($showEdit)
         {
            print "<th>Edit</th>";
         }
         print "</tr>";
      }
      
      // Print a row for each player
      for ($i = 0; ($i < $numberOfPlayersDisplayed) && ($i < count($players)); $i++)
      {
         $player = $players[$i];

         if ($player["customized"])
         {
            print "<tr class=\"cus\">";
         }
         elseif ($i % 2)
         {
            print "<tr class=\"alt\">";
         }
         else
         {
            print "<tr>";
         }
         print "<td><a name=\"#" . $player["mlbamID"] . "\">" . $player["playerName"] . "</a></td>";
         print "<td>" . $this->formatTeam($player["team"]) . "</td>";
         print "<td>" . $this->buildPositionList($player, $positions) . "</td>";

         if (!$this->displayStatsOnly)
         {
            if (!$this->pointsLeague)
            {
               print "<td class='number' style='background-color:#" . $this->calculateCategoryColor("dollarValue", $player["dollarValue"]) . ";'>" . $this->formatDollar($player["dollarValue"], $minimumBid) . "</td>";
            }
            else
            {
               print "<td class='number'>" . $this->formatDollar($player["dollarValue"], $minimumBid) . "</td>";
            }

            if ($useKeepers)
            {
               print "<td class='number'>" . $this->formatDollar($player["adjustedDollarValue"], $minimumBid) . "</td>";
               print "<td class='number' id=\"keeperPrice\">";
               if ((!is_null($player["keeperPrice"])) && ($player["keeperPrice"] != ""))
               {
                  print $this->formatDollar($player["keeperPrice"], $minimumBid);
               }
               else
               {
                  print "&nbsp;";
               }
               print "</td>";
            }

            // Show category dollar values
            if (!$this->pointsLeague)
            {
               foreach ($categories as $category)
               {
                  print "<td class='number' style='background-color:#" . $this->calculateCategoryColor($category, $player["m" . $category]) . ";'>" . $this->formatDollar($player[$category . "$"], $minimumBid) . "</td>";
               }
               print "<td class='number'>" . $this->formatDollar($player["Pos$"], $minimumBid) . "</td>";
            }

         }

         // Show raw category stats
         if (!$this->pointsLeague)
         {
            foreach ($categories as $category)
            {
               if ($category[0] == "x")
               {
                  $stat = $player[substr($category, 1)];
               }
               else
               {
                  $stat = $player[$category];
               }

               // Show more details in debug mode
               if ($debugMode)
               {
                  print "<td class='number' style='background-color:#" . $this->calculateCategoryColor($category, $player["m" . $category]) . ";'>" . number_format($player["m" . $category], 2) . "</td>";
               }
               // Don't add color when just displaying stats
               elseif ($this->displayStatsOnly)
               {
                  print "<td class='number' id='" . $category . "'>" . $this->formatStat($category, $stat) . "</td>";
               }
               else
               {
                  print "<td class='number' style='background-color:#" . $this->calculateCategoryColor($category, $player["m" . $category]) . ";' id='" . $category . "'>" . $this->formatStat($category, $stat) . "</td>";
               }
            }
         }
         else
         {
            foreach ($categories as $category => $value)
            {
               $stat = $player[$category];
               print "<td class='number' id='" . $category . "'>" . $this->formatStat($category, $stat) . "</td>";
            }
         }

         if (!$this->displayStatsOnly)
         {
            // Show component stats
            foreach ($components as $key => $component)
            {
               if (!in_array($component, $categories))
               {
                  print "<td class='number' id='" . $component . "'>" . number_format($player[$component]) . "</td>";
               }
            }

            // Show totals
            if ($debugMode || $this->pointsLeague)
            {
               if ($debugMode)
               {
                  $precision = 2;
               }
               else
               {
                  $precision = 0;
               }
               print "<td class='number'>". number_format($player['total'], $precision) . "</td>";
               print "<td class='number'>" . number_format($player['adjTotal'], $precision) . "</td>";
            }

            // Show edit button
            if ($showEdit)
            {
               print "<td><a href=\"#" . $player["mlbamID"] . "\" onclick=\"" . $playerType . "Edit(this)\">Edit</a></td>";
            }
         }
         print "</tr>";
      }
      print "</table></div>";
   }
   
   private function calculateMinAndMax($players, $categories)
   {
      $this->categoryMins = array();
      $this->categoryMaxes = array();
      $this->categoryMidpoints = array();

      foreach ($categories as $category)
      {
         $min = PHP_INT_MAX;
         $max = PHP_INT_MIN;

         foreach ($players as $player)
         {
            // Quit looking at players once we hit replacement level...
            if ($player["adjTotal"] <= 0)
            {
               break;
            }
            $min = min($min, $player["m" . $category]);
            $max = max($max, $player["m" . $category]);
         }
         $this->categoryMins[$category] = $min;
         $this->categoryMaxes[$category] = $max;
         $this->categoryMidpoints[$category] = ($max + $min) / 2;
      }
      
      $this->categoryMaxes["dollarValue"] = $players[0]["dollarValue"];
      foreach ($players as $player)
      {
         if ($player["adjTotal"] <= 0)
         {
            $this->categoryMins["dollarValue"] = $player["dollarValue"];
            break;
         }
      }
      $this->categoryMidpoints["dollarValue"] = ($this->categoryMaxes["dollarValue"] + $this->categoryMins["dollarValue"]) / 2;
   
   }
   
   private function calculateCategoryColor($category, $value)
   {
      if ($value >= $this->categoryMidpoints[$category])
      {
         $range = $this->categoryMaxes[$category] - $this->categoryMidpoints[$category];
         $subRange = $this->categoryMaxes[$category] - $value;
         if ($range > 0)
         {
            $pct = $subRange / $range * 100;
         }
         else
         {
            $pct = 0;
         }
         $colorValue = 155 + $pct;
         return dechex($colorValue) . "ff" . dechex($colorValue);
      }
      else
      {
         $range = $this->categoryMidpoints[$category] - $this->categoryMins[$category];
         $subRange = $value - $this->categoryMins[$category];
         if ($range > 0)
         {
            $pct = $subRange / $range * 100;
         }
         else
         {
            $pct = 0;
         }
         $colorValue = 155 + $pct;
         return "ff" . dechex($colorValue) . dechex($colorValue);
      }
      
   
   }
   
   private function formatDollar($dollarValue, $roundTo)
   {
      $significantDigits = strlen(strrchr($roundTo, ".")) - 1;
      $precision = 1 / $roundTo;
      
      if ($dollarValue >= 0)
      {
         return "$" . number_format(round($dollarValue * $precision) / $precision, $significantDigits);
      }
      else
      {
         return "-$" . number_format(abs(round($dollarValue * $precision) / $precision), $significantDigits);
      }
   }

   private function formatStat($category, $stat)
   {
      switch ($category)
      {
         case "xAVG":
         case "xOBP":
         case "xSLG":
         case "xOPS":
         case "xBAA":
            $stat = number_format($stat, 3);
            break;
         case "xERA":
         case "xK9":
         case "xBB9":
         case "xHR9":
            if ($stat < 1000)
            {
               $stat = number_format(($stat * 9), 2);
            }
            else
            {
               $stat = "INF";
            }
            break;
         case "xWHIP":
         case "xKBB":
         case "xW%":
         case "xSB%":
         case "xKAB":
            if ($stat < 1000)
            {
               $stat = number_format($stat, 2);
            }
            else
            {
               $stat = "INF";
            }
            break;
         default:
            $stat = number_format($stat);
            break;
      }

      return $stat;
   }

   private function formatTeam($team)
   {
      switch ($team)
      {
         case "CHN":
            return "CHC";
         case "CHA":
            return "CWS";
         case "FLO":
         case "FLA":
            return "FL";
         case "KCA":
            return "KC";
         case "ANA":
            return "LAA";
         case "LAN":
            return "LAD";
         case "NYN":
            return "NYM";
         case "NYA":
            return "NYY";
         case "SDN":
            return "SD";
         case "SFN":
            return "SF";
         case "SLN":
            return "STL";
         case "TBA":
            return "TB";
         case "":
            return "&nbsp;";
         default:
            return $team;
      }
   }

   private function printEditForm($components, $categories, $useKeepers, $customDataset, $playerType, $debugMode)
   {
      if ($useKeepers)
      {
         $NUMBER_OF_COLUMNS = 9;
      }
      else
      {
         $NUMBER_OF_COLUMNS = 7;
      }
      $numberOfExtraStats = 0;
      foreach ($components as $key => $component)
      {
         if (!in_array($component, $categories))
         {
            $numberOfExtraStats++;
         }
      }
      $numberOfColumns = $NUMBER_OF_COLUMNS + count($categories) + $numberOfExtraStats;
      if ($debugMode)
      {
         $numberOfColumns += count($categories);
      }

      print "<table style=\"display:none\"><tr><td id=\"" . $playerType . "Edit\" colspan=\"" . $numberOfColumns . "\" style=\"border: solid #000000 1px\">";
      print "<form>";
      
      print "<input type=\"hidden\" name=\"mlbamID\" id=\"mlbamID\" value=\"\" />";
      print "Kept at: <input type=\"text\" name=\"keeperPrice\" size=\"1\" /> ";

      foreach ($components as $key => $component)
      {
         print $component . ": <input type=\"text\" name=\"" . $component . "\" size=\"1\" /> ";
      }
      print " <a href=\"#\" id=\"saveLink\" onclick=\"savePlayer(this)\">Save changes</a>";
      print "<input type=\"hidden\" name=\"i\" id=\"i\" value=\"" . $customDataset . "\" />";

      print "</form></td></tr></table>";
   }

   private function printKeeperEditForm($components, $categories, $useKeepers, $customDataset, $playerType, $debugMode)
   {
      if ($useKeepers)
      {
         $NUMBER_OF_COLUMNS = 9;
      }
      else
      {
         $NUMBER_OF_COLUMNS = 7;
      }
      $numberOfExtraStats = 0;
      foreach ($components as $key => $component)
      {
         if (!in_array($component, $categories))
         {
            $numberOfExtraStats++;
         }
      }
      $numberOfColumns = $NUMBER_OF_COLUMNS + count($categories) + $numberOfExtraStats;
      if ($debugMode)
      {
         $numberOfColumns += count($categories);
      }

      print "<table style=\"display:none\"><tr><td id=\"" . $playerType . "Edit\" colspan=\"" . $numberOfColumns . "\" style=\"border: solid #000000 1px\">";
      print "<form>";
      
      print "<input type=\"hidden\" name=\"mlbamID\" id=\"mlbamID\" value=\"\" />";
      print "Kept at: <input type=\"text\" name=\"keeperPrice\" size=\"1\" /> ";

      print " <a href=\"#\" id=\"saveLink\" onclick=\"savePlayer(this)\">Save changes</a>";
      print "<input type=\"hidden\" name=\"i\" id=\"i\" value=\"" . $customDataset . "\" />";

      print "</form></td></tr></table>";
   }

   private function printStatEditForm($components, $categories, $useKeepers, $customDataset, $playerType, $debugMode)
   {
      if ($useKeepers)
      {
         $NUMBER_OF_COLUMNS = 9;
      }
      else
      {
         $NUMBER_OF_COLUMNS = 7;
      }
      $numberOfExtraStats = 0;
      foreach ($components as $key => $component)
      {
         if (!in_array($component, $categories))
         {
            $numberOfExtraStats++;
         }
      }
      $numberOfColumns = $NUMBER_OF_COLUMNS + count($categories) + $numberOfExtraStats;
      if ($debugMode)
      {
         $numberOfColumns += count($categories);
      }

      print "<table style=\"display:none\"><tr><td id=\"" . $playerType . "Edit\" colspan=\"" . $numberOfColumns . "\" style=\"border: solid #000000 1px\">";
      print "<form>";
      
      print "<input type=\"hidden\" name=\"mlbamID\" id=\"mlbamID\" value=\"\" />";

      foreach ($components as $key => $component)
      {
         print $component . ": <input type=\"text\" name=\"" . $component . "\" size=\"1\" /> ";
      }
      print " <a href=\"#\" id=\"saveLink\" onclick=\"savePlayer(this)\">Save changes</a>";
      print "<input type=\"hidden\" name=\"i\" id=\"i\" value=\"" . $customDataset . "\" />";

      print "</form></td></tr></table>";
   }

}


?>