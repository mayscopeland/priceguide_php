<?php 

$doCairoHitters = true;
$doCairoPitchers = true;
$doComposite411Hitters = true;
$doComposite411Pitchers = true;
$doMarcelHitters = false;
$doMarcelPitchers = false;
$doZipsHitters = false;
$doZipsPitchers = false;
$doSteamerHitters = true;
$doSteamerPitchers = true;

if ($doCairoHitters)
{
   $cairoHitters = array();
   loadStats($cairoHitters, "13C", "Hitters");
   loadTeamsAndDefaultPositions($cairoHitters);
   loadGamesByPosition($cairoHitters, "Hitters");
   saveStats($cairoHitters, "13C", "Hitters");
}

if ($doCairoPitchers)
{
   $cairoPitchers = array();
   loadStats($cairoPitchers, "13C", "Pitchers");
   loadTeamsAndDefaultPositions($cairoPitchers);
   loadGamesByPosition($cairoPitchers, "Pitchers");
   saveStats($cairoPitchers, "13C", "Pitchers");
}

if ($doComposite411Hitters)
{
   $composite411Hitters = array();
   loadStats($composite411Hitters, "134", "Hitters");
   loadTeamsAndDefaultPositions($composite411Hitters);
   loadGamesByPosition($composite411Hitters, "Hitters");
   saveStats($composite411Hitters, "134", "Hitters");
}

if ($doComposite411Pitchers)
{
   $composite411Pitchers = array();
   loadStats($composite411Pitchers, "134", "Pitchers");
   loadTeamsAndDefaultPositions($composite411Pitchers);
   loadGamesByPosition($composite411Pitchers, "Pitchers");
   saveStats($composite411Pitchers, "134", "Pitchers");
}

if ($doMarcelHitters)
{
   $marcelHitters = array();
   loadStats($marcelHitters, "13M", "Hitters");
   loadTeamsAndDefaultPositions($marcelHitters);
   loadGamesByPosition($marcelHitters, "Hitters");
   saveStats($marcelHitters, "13M", "Hitters");
}

if ($doMarcelPitchers)
{
   $marcelPitchers = array();
   loadStats($marcelPitchers, "13M", "Pitchers");
   loadTeamsAndDefaultPositions($marcelPitchers);
   loadGamesByPosition($marcelPitchers, "Pitchers");
   saveStats($marcelPitchers, "13M", "Pitchers");
}

if ($doZipsHitters)
{
   $zipsHitters = array();
   loadStats($zipsHitters, "13Z", "Hitters");
   loadTeamsAndDefaultPositions($zipsHitters);
   loadGamesByPosition($zipsHitters, "Hitters");
   saveStats($zipsHitters, "13Z", "Hitters");
}

if ($doZipsPitchers)
{
   $zipsPitchers = array();
   loadStats($zipsPitchers, "13Z", "Pitchers");
   loadTeamsAndDefaultPositions($zipsPitchers);
   loadGamesByPosition($zipsPitchers, "Pitchers");
   saveStats($zipsPitchers, "13Z", "Pitchers");
}

if ($doSteamerHitters)
{
   $steamerHitters = array();
   loadStats($steamerHitters, "13E", "Hitters");
   loadTeamsAndDefaultPositions($steamerHitters);
   loadGamesByPosition($steamerHitters, "Hitters");
   saveStats($steamerHitters, "13E", "Hitters");
}

if ($doSteamerPitchers)
{
   $steamerPitchers = array();
   loadStats($steamerPitchers, "13E", "Pitchers");
   loadTeamsAndDefaultPositions($steamerPitchers);
   loadGamesByPosition($steamerPitchers, "Pitchers");
   saveStats($steamerPitchers, "13E", "Pitchers");
}

function buildPlayerLines($players)
{
   $lines = "";

   foreach ($players[0] as $col => $value)
   {
      $cols[] = $col;
      $lines .= $col . ",";
   }
   $lines = substr($lines, 0, strlen($lines) - 1);
   $lines .= "\n";

   foreach ($players as $player)
   {
      foreach ($cols as $col)
      {
         $lines .= $player[$col] . ",";
      }
      $lines = substr($lines, 0, strlen($lines) - 1);
      $lines .= "\n";
   }
   
   return $lines;
}

function loadTeamsAndDefaultPositions(&$players)
{
   $handle = fopen("stats/Teams2013.csv", "r");
   
   if ($handle)
   {

      foreach ($players as &$player)
      {

         $player["team"] = "";
         $player["league"] = "";
         $player["defaultPos"] = "";
      }

      while (!feof($handle))
      {
         $teamLine = fgetcsv($handle);
   
         foreach ($players as &$player)
         {
            if (($teamLine[1]) && ($teamLine[1] == $player["mlbamID"]))
            {
               $player["team"] = $teamLine[2];
               $player["league"] = $teamLine[3];
               $player["defaultPos"] = $teamLine[4];
            }
         }
      }
      fclose($handle);
   }
}

function loadGamesByPosition(&$players, $playerType)
{

   $handle = fopen("stats/GamesByPos2012.csv", "r");
 
   if ($handle)
   {
      if (!feof($handle))
      {
         $columns = fgetcsv($handle);
      }

      foreach ($players as &$player)
      {
         if ($playerType == "Hitters")
         {
            $player["G_C"]  = 0;
            $player["G_1B"] = 0;
            $player["G_2B"] = 0;
            $player["G_3B"] = 0;
            $player["G_SS"] = 0;
            $player["G_LF"] = 0;
            $player["G_CF"] = 0;
            $player["G_RF"] = 0;
            $player["G_OF"] = 0;
         }
         else
         {
            $player["G_SP"] = 0;
            $player["G_RP"] = 0;
         }
      }

      while (!feof($handle))
      {
         $playerGames = fgetcsv($handle);

         foreach ($players as &$player)
         {
            if (($playerGames) && ($playerGames[0] == $player["mlbamID"]))
            {
               if ($playerType == "Hitters")
               {
                  $player["G_C"]  = $playerGames[6];
                  $player["G_1B"] = $playerGames[7];
                  $player["G_2B"] = $playerGames[8];
                  $player["G_3B"] = $playerGames[9];
                  $player["G_SS"] = $playerGames[10];
                  $player["G_LF"] = $playerGames[11];
                  $player["G_CF"] = $playerGames[12];
                  $player["G_RF"] = $playerGames[13];
                  $player["G_OF"] = $playerGames[14];
               }
               else
               {
                  $player["G_SP"] = $playerGames[5];
                  $player["G_RP"] = $playerGames[4] - $playerGames[5];
               }
            }
         }

      }
      fclose($handle);
   }
}
/*
function loadSaves(&$players)
{

   $handle = fopen("stats/Saves2012.csv", "r");
 
   if ($handle)
   {
      if (!feof($handle))
      {
         $columns = fgetcsv($handle);
      }

      foreach ($players as &$player)
      {
         $player["S"] = 0;
      }

      while (!feof($handle))
      {
         $saves = fgetcsv($handle);

         foreach ($players as &$player)
         {
            if (($saves) && ($saves[0] == $player["mlbamID"]))
            {
               $player["S"] = $saves[3];
            }
         }

      }
      fclose($handle);
   }
}
*/

function loadStats(&$players, $dataset, $playerType)
{
   $handle = getFileHandle($dataset, $playerType, "r");

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

function saveStats(&$players, $dataset, $playerType)
{
   $handle = getFileHandle($dataset, $playerType, "w");
   fwrite($handle, buildPlayerLines($players));
   fclose($handle);
}

function getFileHandle($dataset, $playerType, $readOrWrite)
{
   switch ($dataset)
   {
       case "134":
         if ($playerType == "Hitters")
         {
            $handle = fopen("stats/2013BattingComposite411.csv", $readOrWrite);
         }
         else
         {
            $handle = fopen("stats/2013PitchingComposite411.csv", $readOrWrite);
         }
         break;
      case "13C":
         if ($playerType == "Hitters")
         {
            $handle = fopen("stats/2013BattingCAIRO3.csv", $readOrWrite);
         }
         else
         {
            $handle = fopen("stats/2013PitchingCAIRO3.csv", $readOrWrite);
         }
         break;
      case "13E":
         if ($playerType == "Hitters")
         {
            $handle = fopen("stats/2013BattingSteamer.csv", $readOrWrite);
         }
         else
         {
            $handle = fopen("stats/2013PitchingSteamer.csv", $readOrWrite);
         }
         break;
      case "13M":
         if ($playerType == "Hitters")
         {
            $handle = fopen("stats/2013BattingMarcel.csv", $readOrWrite);
         }
         else
         {
            $handle = fopen("stats/2013PitchingMarcel.csv", $readOrWrite);
         }
         break;
      case "13Z":
         if ($playerType == "Hitters")
         {
            $handle = fopen("stats/2013BattingZiPS.csv", $readOrWrite);
         }
         else
         {
            $handle = fopen("stats/2013PitchingZiPS.csv", $readOrWrite);
         }
         break;
      case "13T":
         if ($playerType == "Hitters")
         {
            $handle = fopen("templates/BattingTemplate.csv", $readOrWrite);
         }
         else
         {
            $handle = fopen("templates/PitchingTemplate.csv", $readOrWrite);
         }
         break;
   }
   
   return $handle;
}

?>