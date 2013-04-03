<?php 

define(YAHOO, 1);
define(ALL_STAR_STATS, 2);
define(CBS, 3);
define(ESPN, 4);

if ($_GET)
{
   $queryString = $_SERVER["QUERY_STRING"];
   $queryString = preg_replace("/&gm=.&x=\.user\.js/", "", $queryString);
   $leagueType = 0;
   
   switch($_GET["gm"])
   {
      case "Y":
         $leagueType = YAHOO;
         break;
      case "A":
         $leagueType = ALLSTAR_STATS;
         break;
      case "C":
         $leagueType = CBS;
         break;
      case "E":
         $leagueType = ESPN;
         break;
   }

   header("Content-type: text/javascript");
   header("Content-Disposition: filename=lpp_pg.user.js");
   $outstream = fopen("php://output",'w');
      
   fwrite($outstream, printJavaScript($leagueType, $queryString));

   fclose($outstream);
}

function getLeagueName($leagueType)
{
   switch($leagueType)
   {
      case YAHOO:
         return "Yahoo";
         break;
      case ALLSTAR_STATS:
         return "Allstar Stats";
         break;
      case CBS:
         return "CBS";
         break;
      case ESPN:
         return "ESPN";
         break;
   }
}

function getLeagueIncludes($leagueType)
{
   switch($leagueType)
   {
      case YAHOO:
         return "http://baseball.fantasysports.yahoo.com/*";
         break;
      case ALLSTAR_STATS:
         return "http://allstar.rotoworld.com/*";
         break;
      case CBS:
         return "*.baseball.cbssports.com/*";
         break;
      case ESPN:
         return "http://games.espn.go.com/flb/*";
         break;
   }
}

function getLeagueIdIndex($leagueType)
{
   switch($leagueType)
   {
      case YAHOO:
         return "0";
         break;
      case ALLSTAR_STATS:
         return "0";
         break;
      case CBS:
         return "2";
         break;
      case ESPN:
         return "3";
         break;
   }
}

function getLeagueMatch($leagueType)
{
   switch($leagueType)
   {
      case YAHOO:
         return ".*sports\.yahoo\.com\/mlb\/players\/(\d\d\d\d)$";
         break;
      case ALLSTAR_STATS:
         return ".*&X=(\d\d\d\d).*";
         break;
      case CBS:
         return ".*\/players\/playerpage\/(\d+)$";
         break;
   }
}

function printJavaScript($leagueType, $queryString)
{
   $javaScript = "";
   
   $javaScript .= "// ==UserScript==\n";
   $javaScript .= "// @name           Fantasy Baseball Price Guide for " . getLeagueName($leagueType) . "\n";
   $javaScript .= "// @namespace      http://www.lastplayerpicked.com/priceguide/index.php?" . $queryString . "\n";
   $javaScript .= "// @copyright      2009, Mays Copeland (http://www.lastplayerpicked.com)\n";
   $javaScript .= "// @include        " . getLeagueIncludes($leagueType) . "\n";
   $javaScript .= "// ==/UserScript==\n";
   $javaScript .= "(function() {\n";
   $javaScript .= "\n";
   $javaScript .= "var priceGuideURL = \"http://www.lastplayerpicked.com/priceguide/index.php?" . $queryString . "&o=S\"\n";
   $javaScript .= "var players = new Array();\n";
   $javaScript .= "\n";
   $javaScript .= "getPlayers();\n";
   $javaScript .= "\n";
   
   if ( ($leagueType == CBS) || ($leagueType == ESPN) )
   {
      $javaScript .= "document.addEventListener(\"DOMNodeInserted\", getInfo, false);\n";
      $javaScript .= "\n";
      $javaScript .= "function getInfo(event)\n";
      $javaScript .= "{\n";
      $javaScript .= "   if (event.target.tagName == \"TABLE\")\n";
      $javaScript .= "   {\n";
      $javaScript .= "      showValues();\n";
      $javaScript .= "   }\n";
      $javaScript .= "}\n";
      $javaScript .= "\n";
   }
   $javaScript .= "function getPlayers()\n";
   $javaScript .= "{\n";
   $javaScript .= "GM_xmlhttpRequest(\n";
   $javaScript .= "{\n";
   $javaScript .= "    method: 'GET',\n";
   $javaScript .= "    url: priceGuideURL,\n";
   $javaScript .= "    onload: function( responseDetails )\n";
   $javaScript .= "    {\n";
   $javaScript .= "      buildPlayersArray(responseDetails.responseText);\n";
   $javaScript .= "      showValues();\n";
   $javaScript .= "    },\n";
   $javaScript .= "});\n";
   $javaScript .= "}\n";
   $javaScript .= "\n";
   $javaScript .= "function buildPlayersArray(playersCSV)\n";
   $javaScript .= "{\n";
   $javaScript .= "   var playerValues = playersCSV.split(\"\\n\");\n";
   $javaScript .= "\n";
   $javaScript .= "   for (var i = 0; i < playerValues.length - 1; i++)\n";
   $javaScript .= "   {\n";
   $javaScript .= "      players[i] = new Object();\n";
   $javaScript .= "\n";
   $javaScript .= "      var player = playerValues[i].split(\",\");\n";
   $javaScript .= "\n";
   $javaScript .= "      players[i].playerID = player[" . getLeagueIdIndex($leagueType) . "];\n";
   $javaScript .= "      if (player[1] > 0)\n";
   $javaScript .= "      {\n";
   $javaScript .= "         players[i].dollarValue = \"$\" + Number(player[1]).toFixed(0);\n";
   $javaScript .= "      }\n";
   $javaScript .= "      else\n";
   $javaScript .= "      {\n";
   $javaScript .= "         players[i].dollarValue =  \"-$\" + Math.abs(Number(player[1]).toFixed(0));\n";
   $javaScript .= "      }\n";
   $javaScript .= "   }\n";
   $javaScript .= "}\n";
   $javaScript .= "\n";
   $javaScript .= "function showValues()\n";
   $javaScript .= "{\n";
   if ($leagueType == ESPN)
   {
      $javaScript .= "   var tags = document.getElementsByTagName(\"div\");\n";
   }
   else
   {
      $javaScript .= "   var playerMatch = /" . getLeagueMatch($leagueType) . "/;\n";
      $javaScript .= "\n";
      $javaScript .= "   var tags = document.getElementsByTagName(\"a\");\n";
   }
   $javaScript .= "\n";
   $javaScript .= "   for (var i = 0; i < tags.length; i++)\n";
   $javaScript .= "   {\n";
   if ($leagueType == ESPN)
   {
      $javaScript .= "      var playerID = tags[i].getAttribute(\"player_id\");\n";
      $javaScript .= "\n";
      $javaScript .= "      if (playerID)\n";
      $javaScript .= "      {\n";
   }
   else
   {
      $javaScript .= "      var result = tags[i].href.match(playerMatch);\n";
      $javaScript .= "\n";
      $javaScript .= "      if (result != null)\n";
      $javaScript .= "      {\n";
      $javaScript .= "         var playerID = result[1];\n";
   }
   $javaScript .= "         for (var j = 0; j < players.length; j++)\n";
   $javaScript .= "         {\n";
   $javaScript .= "            if (playerID == players[j].playerID)\n";
   $javaScript .= "            {\n";
   $javaScript .= "               if (tags[i].innerHTML.indexOf(players[j].dollarValue) < 0)\n";
   $javaScript .= "               {\n";
   $javaScript .= "                  tags[i].innerHTML = tags[i].innerHTML + \" \" + players[j].dollarValue;\n";
   $javaScript .= "               }\n";
   $javaScript .= "               break;\n";
   $javaScript .= "            }\n";
   $javaScript .= "         }\n";
   $javaScript .= "      }\n";
   $javaScript .= "   }\n";
   $javaScript .= "}\n";
   $javaScript .= "\n";
   $javaScript .= "})();\n";
   
   return $javaScript;
}

?>