<?php

require_once "ProgressUpdater.php";

class PlayerLoader
{

   public function loadPlayers($request, $playerType, $isHTMLOutput)
   {
      $players = array();
      $progress = new ProgressUpdater();

      $progress->updateProgress("Loading basic stats (" . $playerType . ")...", $isHTMLOutput);

      // Load stats
      if ($request->updatedProjections || $request->restOfSeasonProjections)
      {
         $this->loadStats($projectedPlayers, $request->dataset, $playerType);
         $this->loadStats($players, "11S", $playerType);
         
         $teamGamesPlayed = array();
         $teamGamesPlayed = $this->loadTeamGamesPlayed();
         
         // Extrapolate player stats
         foreach ($players as &$player)
         {
            if ($playerType == "Hitters")
            {
               $stats = array("G","AB","H","2B","3B","HR","R","RBI","SB","CS","BB","SO","SH","SF","HBP");
            }
            else
            {
               $stats = array("IP","W","L","G","S","H","ER","HR","BB","K");
            }

            // Extrapolate player stats
            $pctPlayed = $teamGamesPlayed[$player["team"]]["percentPlayed"];
            foreach ($stats as $stat)
            {
               $player[$stat] = $player[$stat] / $pctPlayed;
            }
            
            foreach ($projectedPlayers as $proj)
            {
               if ($proj["mlbamID"] == $player["mlbamID"])
               {
                  foreach ($stats as $stat)
                  {
                     $player[$stat] = round( (11 * $proj[$stat] + 8 * $player[$stat] * $pctPlayed) / (11 + 8 * $pctPlayed) );
                  }
                  break;
               }
            }
            
            // Reduce new projection by games played
            if ($request->restOfSeasonProjections)
            {
               $pctRemaining = $teamGamesPlayed[$player["team"]]["percentRemaining"];
               foreach ($stats as $stat)
               {
                  $player[$stat] = round($player[$stat] * $pctRemaining);
               }
            }

         }

      }
      else
      {
         $this->loadStats($players, $request->dataset, $playerType);
      }
                                                    
      // Remove for AL/NL only
      if ($request->league != "MLB")
      {
         if ($request->dataset != $request->customId)
         {
            $progress->updateProgress("Removing players outside of " . $request->league . "...", $isHTMLOutput);
            $this->removePlayers($players, $request->league);
         }
      }
      
      // Load playerIDs if this is a CSV
      if (!$isHTMLOutput)
      {
         $this->loadPlayerIds($players, $request->dataset);
      }

      // Load extra stats for pitchers (QS, HLD) if needed
      if ($playerType == "Pitchers")
      {
         // Don't load extra stats if the user uploaded their own stats
         // Custom uploads have a random 8-char dataset name instead of the default 3-char.
         // Don't load extra stats for in-season stats.
         if (strlen($request->dataset) < 8 && $this->isProjectedStats($request->dataset))
         {
            if (in_array("HLD", $request->pitchersInput->categories) || in_array("S+HLD", $request->pitchersInput->categories))
            {
               $this->loadExtraPitchingStats($players, $request->dataset);
            }
            if (in_array("QS", $request->pitchersInput->categories))
            {
               $this->createQualityStartsProjections($players, $request->dataset);
            }
         }
      }

      // Load extra stats for hitters (E, GIDP, A) if needed
      if ($playerType == "Hitters")
      {
         // Don't load extra stats if the user uploaded their own stats
         // Custom uploads have a random 8-char dataset name instead of the default 3-char.
         // Don't load extra stats for in-season stats.
         if (strlen($request->dataset) < 8 && $this->isProjectedStats($request->dataset))
         {
            if (in_array("E", $request->hittersInput->categories) || in_array("GIDP", $request->hittersInput->categories) || in_array("A", $request->hittersInput->categories))
            {
               $this->loadExtraBattingStats($players, $request->dataset);
            }
         }
      }

      // Load custom datasets
      if ($request->customId || $request->keeperId)
      {
         if ($request->customId)
         {
            $this->loadCustomStats($players, $request->customId);
         }
         else
         {
            $this->loadCustomStats($players, $request->keeperId);
         }
      }

      if ($request->adjustPlayingTime)
      {
         if (($this->is2013Data($request->dataset) && $this->isProjectedStats($request->dataset)) ||
             ($this->is2012Data($request->dataset) && $this->isProjectedStats($request->dataset)) ||
             ($this->is2011Data($request->dataset) && $this->isProjectedStats($request->dataset)))
         {
            //if (!$request->updatedProjections && !$request->restOfSeasonProjections)
            //{
               $this->adjustPlayingTime($players, $playerType, $request->dataset);
            //}
         }
      }

      $progress->updateProgress("Loading number of games played (" . $playerType . ")...", $isHTMLOutput);
      // Load number of games played
      if ($playerType == "Hitters")
      {
         $this->loadGamesByPosition($players, $request->hittersInput->positions, $request->hittersInput->minGames, $request->useTopPosition);
      }
      else
      {
         $this->loadGamesByPosition($players, $request->pitchersInput->positions, $request->pitchersInput->minGames, $request->useTopPosition);
      }
      
      // Load default positions
      $progress->updateProgress("Loading minor league positions (" . $playerType . ")...", $isHTMLOutput);
      if ($playerType == "Hitters")
      {
         $this->loadDefaultPositions($players, $request->hittersInput->positions, $playerType);
      }
      else
      {
         $this->loadDefaultPositions($players, $request->pitchersInput->positions, $playerType);
      }

      return $players;

   }

   private function loadStats(&$players, $dataset, $playerType)
   {
      switch ($dataset)
      {
         case "09A":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2009BattingComposite.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2009PitchingComposite.csv", "r");
            }
            break;
         case "09C":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2009BattingCAIRO.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2009PitchingCAIRO.csv", "r");
            }
            break;
         case "09H":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2009BattingCHONE0223.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2009PitchingCHONE0223.csv", "r");
            }
            break;
         case "09M":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2009BattingMarcel.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2009PitchingMarcel.csv", "r");
            }
            break;
         case "09Z":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2009BattingZiPS.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2009PitchingZiPS.csv", "r");
            }
            break;
         case "10A":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2010BattingComposite.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2010PitchingComposite.csv", "r");
            }
            break;
         case "10C":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2010BattingCAIRO4.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2010PitchingCAIRO4.csv", "r");
            }
            break;
         case "10E":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2010BattingSteamer.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2010PitchingSteamer.csv", "r");
            }
            break;
         case "10H":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2010BattingCHONE0228.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2010PitchingCHONE0228.csv", "r");
            }
            break;
         case "10M":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2010BattingMarcel.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2010PitchingMarcel.csv", "r");
            }
            break;
         case "10F":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2010BattingCompositeCF.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2010PitchingCompositeCF.csv", "r");
            }
            break;
         case "10Z":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2010BattingZiPS.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2010PitchingZiPS.csv", "r");
            }
            break;
         case "08S":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2008Batting.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2008Pitching.csv", "r");
            }
            break;
         case "09S":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2009Batting.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2009Pitching.csv", "r");
            }
            break;
         case "10S":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2010Batting.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2010Pitching.csv", "r");
            }
            break;
         case "11S":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2011Batting.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2011Pitching.csv", "r");
            }
            break;
         case "12S":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2012Batting.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2012Pitching.csv", "r");
            }
            break;
         case "11C":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2011BattingCAIRO6.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2011PitchingCAIRO6.csv", "r");
            }
            break;
         case "11M":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2011BattingMarcel.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2011PitchingMarcel.csv", "r");
            }
            break;
         case "11E":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2011BattingSteamer.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2011PitchingSteamer.csv", "r");
            }
            break;
         case "11Z":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2011BattingZiPS.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2011PitchingZiPS.csv", "r");
            }
            break;
         case "114":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2011BattingComposite411.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2011PitchingComposite411.csv", "r");
            }
            break;
         case "12C":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2012BattingCAIRO5.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2012PitchingCAIRO5.csv", "r");
            }
            break;
         case "12E":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2012BattingSteamer.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2012PitchingSteamer.csv", "r");
            }
            break;
         case "124":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2012BattingComposite411.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2012PitchingComposite411.csv", "r");
            }
            break;
         case "13S":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2013Batting.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2013Pitching.csv", "r");
            }
            break;
         case "13C":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2013BattingCAIRO3.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2013PitchingCAIRO3.csv", "r");
            }
            break;
         case "13E":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2013BattingSteamer.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2013PitchingSteamer.csv", "r");
            }
            break;
         case "134":
            if ($playerType == "Hitters")
            {
               $handle = fopen("stats/2013BattingComposite411.csv", "r");
            }
            else
            {
               $handle = fopen("stats/2013PitchingComposite411.csv", "r");
            }
            break;
         default:
            if ($playerType == "Hitters")
            {
               $fileName  = "cusds/" . $dataset . "Batting.csv";
            }
            else
            {
               $fileName  = "cusds/" . $dataset . "Pitching.csv";
            }
            if (file_exists($fileName))
            {
               $handle = fopen($fileName, "r");
            }
            break;
      }

      if ($handle)
      {
         if (!feof($handle))
         {
            $columns = fgetcsv($handle);
         }

         $i = 0;
         while (!feof($handle))
         {
            $player = fgetcsv($handle);
            if ($player > "")
            {
               for ($j = 0; $j < count($player); $j++)
               {
                  $players[$i][$columns[$j]] = $player[$j];
               }
               $i++;
            }
         }
         fclose($handle);
      }
   }

   private function loadCustomStats(&$players, $dataset)
   {
      if (file_exists("cuspl/" . $dataset))
      {
         $handle = fopen("cuspl/" . $dataset, "r");
         
         if ($handle)
         {
            while (!feof($handle))
            {
               $line = fgetcsv($handle);

               if ($line != "")
               {
                  $playersStats = array();
                  foreach ($line as $item)
                  {
                     $playersStats[substr($item, 0, strpos($item, '='))] = substr($item, strpos($item, '=') + 1);
                  }
               }
               
               foreach ($players as &$player)
               {
                  if ($playersStats["mlbamID"] == $player["mlbamID"])
                  {
                     $player["customized"] = true;
                     foreach ($playersStats as $category => $value)
                     {
                        if ($category != "playerName")
                        {
                           $player[$category] = $value;
                        }
                     }
                     break;
                  }
               }
            }
            fclose($handle);
         }     
      }
   }

   private function loadGamesByPosition(&$players, $positions, $minGames, $useTopPosition)
   {
      foreach ($players as &$player)
      {
         if ($useTopPosition)
         {
            $topPosition = "";
            $topGames = 0;
            foreach ($positions as $position => $numStarting)
            {
               if (($numStarting > 0) && ($player["G_" . $position] > $topGames) && ($position != "Util"))
               {
                  $topPosition = $position;
                  $topGames = $player[$position];
               }
            }
            if ($topPosition != "")
            {
               $player["pos"][] = $topPosition;
            }
         }
         else
         {
            foreach ($positions as $position => $numStarting)
            {
               if ($numStarting > 0)
               {
                  if ($player["G_" . $position] >= $minGames[$position])
                  {
                     $player["pos"][] = $position;
                  }
               }
            }
         }
      }
   }

   private function loadTeamGamesPlayed()
   {
      $handle = fopen("stats/TeamGamesPlayed2011.csv", "r");
      
      $teams = array();

      if ($handle)
      {
         while (!feof($handle))
         {
            $teamLine = fgetcsv($handle);
            
            $team = $teamLine[0];
            $teams[$team]["G"] = $teamLine[1];
            $teams[$team]["percentPlayed"] = $teams[$team]["G"] / 162;
            $teams[$team]["percentRemaining"] = 1 - $teams[$team]["percentPlayed"];
         }
         fclose($handle);
      }

      return $teams;
   }

   private function loadExtraBattingStats(&$players, $dataset)
   {
      if ($this->is2012Data($dataset) || $this->is2013Data($dataset))
      {
         $handle = null;
      }
      elseif ($this->is2011Data($dataset))
      {
         $handle = fopen("stats/ExtraBattingStats2010.csv", "r");
      }
      elseif ($this->is2010Data($dataset))
      {
         $handle = fopen("stats/ExtraBattingStats2009.csv", "r");
      }
      else
      {
         $handle = fopen("stats/ExtraBattingStats2008.csv", "r");
      }

      if ($handle)
      {
         while (!feof($handle))
         {
            $statLine = fgetcsv($handle);

            foreach ($players as &$player)
            {
               if (($statLine[0]) && ($statLine[0] == $player["mlbamID"]))
               {
                  $player["E"] = $statLine[1];
                  $player["GIDP"] = $statLine[2];
                  $player["A"] = $statLine[3];
                  break;
               }
            }
         }
         fclose($handle);
      }
   }

   private function loadExtraPitchingStats(&$players, $dataset)
   {
      if ($this->is2012Data($dataset) || $this->is2013Data($dataset))
      {
         $handle = fopen("stats/ExtraPitchingStats2010.csv", "r");
      }
      elseif ($this->is2011Data($dataset))
      {
         $handle = fopen("stats/ExtraPitchingStats2010.csv", "r");
      }
      elseif ($this->is2010Data($dataset))
      {
         $handle = fopen("stats/ExtraPitchingStats2009.csv", "r");
      }
      else
      {
         $handle = fopen("stats/ExtraPitchingStats2008.csv", "r");
      }

      if ($handle)
      {
         while (!feof($handle))
         {
            $statLine = fgetcsv($handle);

            foreach ($players as &$player)
            {
               if (($statLine[0]) && ($statLine[0] == $player["mlbamID"]))
               {
                  $player["HLD"] = $statLine[1];
                  break;
               }
            }
         }
         fclose($handle);
      }
   }

   private function createQualityStartsProjections(&$players, $dataset)
   {
      foreach ($players as &$player)
      {
         if ($player["IP"] > 120)
         {
            // From Zac Hinz via email
            $era = $player["ER"] / $player["IP"] * 9;
            $player["QS"] = round(6.35 + .095 * $player["IP"] - 1.6 * $era);
         }
      }
   }

   private function loadPlayerIds(&$players)
   {
      $handle = fopen("stats/IdConversion.csv", "r");

      if ($handle)
      {
         while (!feof($handle))
         {
            $teamLine = fgetcsv($handle);

            foreach ($players as &$player)
            {
               if (($teamLine[0]) && ($teamLine[0] == $player["mlbamID"]))
               {
                  $player["statsID"] = $teamLine[3];
                  $player["cbsID"] = $teamLine[4];
                  $player["espnID"] = $teamLine[5];
                  break;
               }
            }
         }
         fclose($handle);
      }
   }

   private function adjustPlayingTime(&$players, $playerType, $dataset)
   {
      if ($this->is2011Data($dataset))
      {
         if ($playerType == "Hitters")
         {
             $handle = fopen("stats/BattingPlayingTime2011.csv", "r");
         }
         else
         {
            $handle = fopen("stats/PitchingPlayingTime2011.csv", "r");
         }
      }
      elseif ($this->is2012Data($dataset))
      {
         if ($playerType == "Hitters")
         {
             $handle = fopen("stats/BattingPlayingTime2012.csv", "r");
         }
         else
         {
            $handle = fopen("stats/PitchingPlayingTime2012.csv", "r");
         }
      }
      elseif ($this->is2013Data($dataset))
      {
         if ($playerType == "Hitters")
         {
             $handle = fopen("stats/BattingPlayingTime2013.csv", "r");
         }
         else
         {
            $handle = fopen("stats/PitchingPlayingTime2013.csv", "r");
         }
      }

      if ($handle)
      {
         while (!feof($handle))
         {
            $playingTimeLine = fgetcsv($handle);

            foreach ($players as &$player)
            {
               if (($playingTimeLine[0]) && ($playingTimeLine[0] == $player["mlbamID"]))
               {
                  if ($playerType == "Hitters")
                  {
                     if ($player["AB"] > 0 || $player["BB"] > 0)
                     {
                        $player["adjustPercent"] = $playingTimeLine[1] / ($player["AB"] + $player["BB"] + $player["SH"] + $player["HBP"]);
                     }
                  }
                  else
                  {
                     if ($player["IP"] > 0)
                     {
                        $player["adjustPercent"] = $playingTimeLine[1] / $player["IP"];
                        
                        if ($playingTimeLine[3])
                        {
                           $player["S"] = $playingTimeLine[3];
                        }
                        else
                        {
                           $player["S"] = "";
                        }
                     }
                  }
                  break;
               }
            }
         }
         fclose($handle);
         
         for ($i = count($players) - 1; $i > -1; $i--)
         {
            if ($players[$i]["adjustPercent"] > 0)
            {
               if ($playerType == "Hitters")
               {
                  $players[$i]["AB"] = round($players[$i]["adjustPercent"] * $players[$i]["AB"]);
                  $players[$i]["H"] = round($players[$i]["adjustPercent"] * $players[$i]["H"]);
                  $players[$i]["2B"] = round($players[$i]["adjustPercent"] * $players[$i]["2B"]);
                  $players[$i]["3B"] = round($players[$i]["adjustPercent"] * $players[$i]["3B"]);
                  $players[$i]["HR"] = round($players[$i]["adjustPercent"] * $players[$i]["HR"]);
                  $players[$i]["R"] = round($players[$i]["adjustPercent"] * $players[$i]["R"]);
                  $players[$i]["RBI"] = round($players[$i]["adjustPercent"] * $players[$i]["RBI"]);
                  $players[$i]["SB"] = round($players[$i]["adjustPercent"] * $players[$i]["SB"]);
                  $players[$i]["CS"] = round($players[$i]["adjustPercent"] * $players[$i]["CS"]);
                  $players[$i]["BB"] = round($players[$i]["adjustPercent"] * $players[$i]["BB"]);
                  $players[$i]["SO"] = round($players[$i]["adjustPercent"] * $players[$i]["SO"]);
                  $players[$i]["SH"] = round($players[$i]["adjustPercent"] * $players[$i]["SH"]);
                  $players[$i]["SF"] = round($players[$i]["adjustPercent"] * $players[$i]["SF"]);
                  $players[$i]["HBP"] = round($players[$i]["adjustPercent"] * $players[$i]["HBP"]);
               }
               else
               {
                  $players[$i]["IP"] = round($players[$i]["adjustPercent"] * $players[$i]["IP"]);
                  $players[$i]["W"] = round($players[$i]["adjustPercent"] * $players[$i]["W"]);
                  $players[$i]["L"] = round($players[$i]["adjustPercent"] * $players[$i]["L"]);
                  $players[$i]["G"] = round($players[$i]["adjustPercent"] * $players[$i]["L"]);
                  //$players[$i]["S"] = round($players[$i]["adjustPercent"] * $players[$i]["S"]);
                  $players[$i]["H"] = round($players[$i]["adjustPercent"] * $players[$i]["H"]);
                  $players[$i]["ER"] = round($players[$i]["adjustPercent"] * $players[$i]["ER"]);
                  $players[$i]["HR"] = round($players[$i]["adjustPercent"] * $players[$i]["HR"]);
                  $players[$i]["BB"] = round($players[$i]["adjustPercent"] * $players[$i]["BB"]);
                  $players[$i]["K"] = round($players[$i]["adjustPercent"] * $players[$i]["K"]);
                  // From Zac Hinz via email
                  if ($player["IP"] > 120)
                  {
                     $era = $players[$i]["ER"] / $players[$i]["IP"] * 9;
                     $players[$i]["QS"] = round(6.35 + .095 * $players[$i]["IP"] - 1.6 * $era);
                  }
                  else
                  {
                     $player["QS"] = 0;
                  }
               }
            }
            else
            {
               unset($players[$i]);
            }
         }

         $players = array_values($players);
      }
   }

   private function loadDefaultPositions(&$players, $positions, $playerType)
   {
      foreach ($players as &$player)
      {
         if (!$player["pos"] && ($player["defaultPos"] != ""))
         {
            if (array_key_exists($player["defaultPos"], $positions))
            {
               $player["pos"][] = $player["defaultPos"];
            }
            elseif (preg_match('/[\/\s]/', $player["defaultPos"]))
            {
            	$playerPositions = preg_split('/[\/\s]+/', $player["defaultPos"]);

            	foreach ($positions as $position => $games)
            	{
                  if (in_array($position, $playerPositions))
                  {
                     $player["pos"][] = $position;
                  }
            	}
            }
         }
         if ($playerType == "Hitters")
         {
            $player["pos"][] = "Util";
         }
         else
         {
            $player["pos"][] = "P";
         }
      }
   }

   private function removePlayers(&$players, $league)
   {
      for ($i = count($players) - 1; $i > -1; $i--)
      {
         if (($players[$i]["league"] != $league) && ($players[$i]["league"]))
         {
            unset($players[$i]);
         }
      }

      $players = array_values($players);
   }
   
   private function isProjectedStats($dataset)
   {
      if (($dataset == "08S") || ($dataset == "09S") || ($dataset == "10S") || ($dataset == "11S") || ($dataset == "12S") || ($dataset == "13S"))
      {
         return false;
      }
      else
      {
         return true;
      }
   }

   private function is2014Data($dataset)
   {
      if (($dataset == "14S") || ($dataset == "144") || ($dataset == "14C") || ($dataset == "14E"))
      {
         return true;
      }
      else
      {
         return false;
      }
   }
   private function is2013Data($dataset)
   {
      if (($dataset == "13S") || ($dataset == "134") || ($dataset == "13C") || ($dataset == "13E"))
      {
         return true;
      }
      else
      {
         return false;
      }
   }
   private function is2012Data($dataset)
   {
      if (($dataset == "12S") || ($dataset == "124") || ($dataset == "12C") || ($dataset == "12E") || ($dataset == "12M") || ($dataset == "12Z"))
      {
         return true;
      }
      else
      {
         return false;
      }
   }
   private function is2011Data($dataset)
   {
      if (($dataset == "11S") || ($dataset == "11A") || ($dataset == "114") || ($dataset == "11C")  || ($dataset == "11E") || ($dataset == "11M") || ($dataset == "11Z"))
      {
         return true;
      }
      else
      {
         return false;
      }
   }
   private function is2010Data($dataset)
   {
      if (($dataset == "10S") || ($dataset == "10A")  ||  ($dataset == "10P")  ||   ($dataset == "10F")  || ($dataset == "10C")  || ($dataset == "10E") || ($dataset == "10H") || ($dataset == "10M") || ($dataset == "10Z"))
      {
         return true;
      }
      else
      {
         return false;
      }
   }

}

?>