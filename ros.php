<?php 

$TR_MATCH = "|<tr class=\"ysprow.*?>(.*?)</tr>|";
$TD_MATCH = "|<td.*?>(.*?)</td>|";

$ch = curl_init("http://sports.yahoo.com/mlb/standings");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);

$html = curl_exec($ch);
$html = cleanupHtml($html);
$teams = array();

preg_match_all($TR_MATCH, $html, $teamMatches);

for ($i = 0; $i < count($teamMatches[1]); $i++)
{   
   preg_match_all($TD_MATCH, $teamMatches[1][$i], $statMatches);

   $teams[$i]["teamName"] = str_replace("&nbsp;", "", strip_tags($statMatches[1][0]));
   $teams[$i]["tm"] = getTeamAbbreviation($teams[$i]["teamName"]);
   $teams[$i]["G"] = intval($statMatches[1][1]) + intval($statMatches[1][2]);

}

outputGamesPlayed($teams);

function outputGamesPlayed($teams)
{

   $outstream = fopen("stats/TeamGamesPlayed2011.csv", 'w');

   fwrite($outstream, "tm,G\n");

   foreach ($teams as $team)
   {
      fwrite($outstream, $team["tm"] . "," . $team["G"] . "\n");
   }

   fclose($outstream);
}



function cleanupHtml($html)
{
   // Strip line breaks
   $html = str_replace("\n", "", $html);
   $html = str_replace("\r", "", $html);

   return $html;
}
function getTeamAbbreviation($teamName)
{
   switch ($teamName)
   {
      case "Arizona Diamondbacks":
         return "ARI";
         break;
      case "Atlanta Braves":
         return "ATL";
         break;
      case "Chicago Cubs":
         return "CHN";
         break;
      case "Cincinnati Reds":
         return "CIN";
         break;
      case "Colorado Rockies":
         return "COL";
         break;
      case "Houston Astros":
         return "HOU";
         break;
      case "Florida Marlins":
         return "FLO";
         break;
      case "Los Angeles Dodgers":
         return "LAN";
         break;
      case "Milwaukee Brewers":
         return "MIL";
         break;
      case "New York Mets":
         return "NYN";
         break;
      case "Philadelphia Phillies":
         return "PHI";
         break;
      case "Pittsburgh Pirates":
         return "PIT";
         break;
      case "San Diego Padres":
         return "SDN";
         break;
      case "San Francisco Giants":
         return "SFN";
         break;
      case "St. Louis Cardinals":
         return "SLN";
         break;
      case "Washington Nationals":
         return "WAS";
         break;
      case "Baltimore Orioles":
         return "BAL";
         break;
      case "Boston Red Sox":
         return "BOS";
         break;
      case "Cleveland Indians":
         return "CLE";
         break;
      case "Chicago White Sox":
         return "CHA";
         break;
      case "Detroit Tigers":
         return "DET";
         break;
      case "Kansas City Royals":
         return "KCA";
         break;
      case "Los Angeles Angels":
         return "LAA";
         break;
      case "Minnesota Twins":
         return "MIN";
         break;
      case "New York Yankees":
         return "NYA";
         break;
      case "Oakland Athletics":
         return "OAK";
         break;
      case "Seattle Mariners":
         return "SEA";
         break;
      case "Tampa Bay Rays":
         return "TBA";
         break;
      case "Texas Rangers":
         return "TEX";
         break;
      case "Toronto Blue Jays":
         return "TOR";
         break;
      default:
         return "";
   }

}

?>