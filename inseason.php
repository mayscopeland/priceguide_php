<?php 

// This is a script that builds CSV files for current season-to-date batting and pitching stats.
// It is meant to run daily (i.e. via cron) during the regular season.
// 
// To get current season stats, this script scrapes Yahoo's baseball stats pages using cURL. It 
// then converts Yahoo's player IDs (from Stats, Inc.) to the player IDs used by the Price Guide,
// (MLBAM IDs). It saves the batter and pitcher CSVs, overwriting the existing files (typically 
// the previous day's year-to-date stats).
//
// During the course of the year, rookies will invariably show up with IDs from Stats Inc. that 
// I don't have mapped to an MLBAM ID. This requires periodically checking the output of this
// script to see which players need their player IDs added to IdConversion.csv.

$year = 2013;

$TR_MATCH = "|<tr height=\"20\" class=\"ysprow.*?>(.*?)</tr>|";
$TD_MATCH = "|<td.*?>(.*?)</td>|";
$A_MATCH  = "<a href=\"\/mlb\/players\/(.*?)\">";

$ch = curl_init("http://sports.yahoo.com/mlb/stats/byposition?pos=LF,CF,RF,1B,2B,SS,3B,C,DH&conference=MLB&year=season_" . $year . "&qualified=0");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);

$html = curl_exec($ch);
//$html = file_get_contents("http://sports.yahoo.com/mlb/stats/byposition?pos=LF,CF,RF,1B,2B,SS,3B,C,DH&conference=MLB&year=season_" . $year . "&qualified=0");
$html = cleanupHtml($html);
$players = array();

preg_match_all($TR_MATCH, $html, $playerMatches);

for ($i = 0; $i < count($playerMatches[1]); $i++)
{   
   preg_match_all($TD_MATCH, $playerMatches[1][$i], $statMatches);
   preg_match($A_MATCH, $statMatches[1][1], $idMatch);
   
   $players[$i]["mlbamID"] = $idMatch[1];
   $players[$i]["playerName"] = strip_tags($statMatches[1][1]);
   $players[$i]["team"] = strip_tags($statMatches[1][2]);
   $players[$i]["league"] = getLeague($players[$i]["team"]);
   $players[$i]["G"] = intval(str_replace("&nbsp;", "", $statMatches[1][3]));
   $players[$i]["AB"] = intval(str_replace("&nbsp;", "", $statMatches[1][5]));
   $players[$i]["R"] = intval(str_replace("&nbsp;", "", $statMatches[1][7]));
   $players[$i]["H"] = intval(str_replace("&nbsp;", "", $statMatches[1][9]));
   $players[$i]["2B"] = intval(str_replace("&nbsp;", "", $statMatches[1][11]));
   $players[$i]["3B"] = intval(str_replace("&nbsp;", "", $statMatches[1][13]));
   $players[$i]["HR"] = intval(str_replace("&nbsp;", "", $statMatches[1][15]));
   $players[$i]["RBI"] = intval(str_replace("&nbsp;", "", $statMatches[1][17]));
   $players[$i]["BB"] = intval(str_replace("&nbsp;", "", $statMatches[1][19]));
   $players[$i]["SO"] = intval(str_replace("&nbsp;", "", $statMatches[1][21]));
   $players[$i]["SB"] = intval(str_replace("&nbsp;", "", $statMatches[1][23]));
   $players[$i]["CS"] = intval(str_replace("&nbsp;", "", $statMatches[1][25]));

}

loadPlayerIds($players);
loadGamesByPosition($players, "Hitters");
loadDefaultPositions($players);
outputPlayers($players, "stats/" . $year . "Batting.csv");

$ch = curl_init("http://sports.yahoo.com/mlb/stats/byposition?pos=SP,RP&conference=MLB&year=season_" . $year . "&qualified=0");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);

$html = curl_exec($ch);

//$html = file_get_contents("http://sports.yahoo.com/mlb/stats/byposition?pos=SP,RP&conference=MLB&year=season_" . $year . "&qualified=0");
$html = cleanupHtml($html);
$players = array();

preg_match_all($TR_MATCH, $html, $playerMatches);

for ($i = 0; $i < count($playerMatches[1]); $i++)
{   
   preg_match_all($TD_MATCH, $playerMatches[1][$i], $statMatches);
   preg_match($A_MATCH, $statMatches[1][1], $idMatch);
   
   $players[$i]["mlbamID"] = $idMatch[1];
   $players[$i]["playerName"] = strip_tags($statMatches[1][1]);
   $players[$i]["team"] = strip_tags($statMatches[1][2]);
   $players[$i]["league"] = getLeague($players[$i]["team"]);
   $players[$i]["G"] = intval(str_replace("&nbsp;", "", $statMatches[1][3]));
   $players[$i]["GS"] = intval(str_replace("&nbsp;", "", $statMatches[1][5]));
   $players[$i]["W"] = intval(str_replace("&nbsp;", "", $statMatches[1][7]));
   $players[$i]["L"] = intval(str_replace("&nbsp;", "", $statMatches[1][9]));
   $players[$i]["S"] = intval(str_replace("&nbsp;", "", $statMatches[1][11]));
   $players[$i]["BS"] = intval(str_replace("&nbsp;", "", $statMatches[1][13]));
   $players[$i]["HLD"] = intval(str_replace("&nbsp;", "", $statMatches[1][15]));
   $players[$i]["CG"] = intval(str_replace("&nbsp;", "", $statMatches[1][17]));
   $players[$i]["SHO"] = intval(str_replace("&nbsp;", "", $statMatches[1][19]));
   
   $ip = str_replace("&nbsp;", "", $statMatches[1][21]);
   $ip = str_replace(".1", ".3", $ip);
   $ip = str_replace(".2", ".7", $ip);
   $players[$i]["IP"] = number_format($ip, 1, ".", "");
   
   $players[$i]["H"] = intval(str_replace("&nbsp;", "", $statMatches[1][23]));
   $players[$i]["R"] = intval(str_replace("&nbsp;", "", $statMatches[1][25]));
   $players[$i]["ER"] = intval(str_replace("&nbsp;", "", $statMatches[1][27]));
   $players[$i]["HR"] = intval(str_replace("&nbsp;", "", $statMatches[1][29]));
   $players[$i]["BB"] = intval(str_replace("&nbsp;", "", $statMatches[1][31]));
   $players[$i]["K"] = intval(str_replace("&nbsp;", "", $statMatches[1][33]));

}

loadPlayerIds($players);
loadGamesByPosition($players, "Pitchers");
loadDefaultPositions($players);
outputPlayers($players, "stats/" . $year . "Pitching.csv");

// Delete the cache
foreach (glob("cache/*.cache") as $file)
{
   unlink($file);
}

function cleanupHtml($html)
{
   // Strip line breaks and remove accents
   $html = str_replace("\n", "", $html);
   $html = str_replace("\r", "", $html);
   $html = str_replace("á", "a", $html);
   $html = str_replace("é", "e", $html);
   $html = str_replace("í", "i", $html);
   $html = str_replace("ó", "o", $html);
   $html = str_replace("ú", "u", $html);
   $html = str_replace("ñ", "n", $html);
   $html = str_replace("Á", "A", $html);
   $html = str_replace("É", "E", $html);
/*
   $html = str_replace("�", "a", $html);
   $html = str_replace("�", "e", $html);
   $html = str_replace("�", "i", $html);
   $html = str_replace("�", "o", $html);
   $html = str_replace("�", "u", $html);
   $html = str_replace("�", "n", $html);
   $html = str_replace("�", "A", $html);
*/
   return $html;
}

function outputPlayers($players, $fileName)
{
   
   $outstream = fopen($fileName, 'w');
   
   if (!$players[0]["defaultPos"])
   {
      $players[0]["defaultPos"] = "";
   }

   foreach ($players[0] as $col => $value)
   {
      fwrite($outstream, $col . ",");
   }
   

   fwrite($outstream, "\n");
   
   foreach ($players as $player)
   {
      foreach ($player as $col)
      {
         fwrite($outstream, $col . ",");
      }
      fwrite($outstream, "\n");
   }
   
   fclose($outstream);
}

function getLeague($team)
{
   global $year;
   
   
   if ($team == "HOU")
   {
      if ($year < 2013)
      {
         return "NL";
      }
      else
      {
         return "AL";
      }
   }


   switch ($team)
   {
      case "ARI":
      case "ATL":
      case "CHC":
      case "CIN":
      case "COL":
      case "FLA": 
      case "LAD":
      case "MIL":
      case "MIA":
      case "NYM":
      case "PHI":
      case "PIT":
      case "SD":
      case "SF":
      case "STL":
      case "WAS":
         return "NL";
         break;
      case "BAL":
      case "BOS":
      case "CLE":
      case "CWS":
      case "DET":
      case "KC":
      case "LAA":
      case "MIN":
      case "NYY":
      case "OAK":
      case "SEA":
      case "TB":
      case "TEX":
      case "TOR":
         return "AL";
         break;
      default:
         return "";  
   }
      
}


function loadPlayerIds(&$players)
{
   $handle = fopen("stats/IdConversion.csv", "r");

   if ($handle)
   {
      while (!feof($handle))
      {
         $teamLine = fgetcsv($handle);

         foreach ($players as &$player)
         {
            if (($teamLine[3]) && ($teamLine[3] == $player["mlbamID"]))
            {
               $player["mlbamID"] = $teamLine[0];
               break;
            }
         }
      }
      fclose($handle);
   }
}


function loadGamesByPosition(&$players, $playerType)
{
   global $year;

   $handle = fopen("stats/GamesByPos" . ($year - 1) . ".csv", "r");

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

function loadDefaultPositions(&$players)
{
   global $year;

   $handle = fopen("stats/Teams" . $year . ".csv", "r");
   
   if ($handle)
   {

      while (!feof($handle))
      {
         $teamLine = fgetcsv($handle);
   
         foreach ($players as &$player)
         {
            if (($teamLine[1]) && ($teamLine[1] == $player["mlbamID"]))
            {
               $player["defaultPos"] = $teamLine[4];
            }
         }
      }
      fclose($handle);
   }
}


?>
