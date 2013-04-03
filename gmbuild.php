<?php 

define(YAHOO, 1);
define(ESPN, 2);
define(CBS, 3);

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
      case "E":
         $leagueType = ESPN;
         break;
      case "C":
         $leagueType = CBS;
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
      case ESPN:
         return "ESPN";
         break;
      case CBS:
         return "CBS";
         break;
   }
}

function getLeagueIncludes($leagueType)
{
   switch($leagueType)
   {
      case YAHOO:
         return "http://baseball.fantasysports.yahoo.com/b1/*";
         break;    
      case ESPN:
         return "http://games.espn.go.com/flb/*";
         break;
      case CBS:
         return "*.baseball.cbssports.com/*";
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
      case CBS:
         return "1";
         break;
      case ESPN:
         return "2";
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
      case CBS:
         return ".*\/players\/playerpage\/(\d+)$";
         break;
   }
}

function printJavaScript($leagueType, $queryString)
{
   $javaScript = "";
   
   $javaScript .= "// ==UserScript==\r\n";
   $javaScript .= "// @name           Fantasy Baseball Price Guide for " . getLeagueName($leagueType) . "\r\n";
   $javaScript .= "// @namespace      http://lastplayerpicked.zxq.net/index.php?" . $queryString . "\r\n";
   $javaScript .= "// @copyright      2013, Mays Copeland\r\n";
   $javaScript .= "// @include        " . getLeagueIncludes($leagueType) . "\r\n";
   $javaScript .= "// @version        2.2\r\n";
   $javaScript .= "// ==/UserScript==\r\n";
   $javaScript .= "(function() {\r\n";
   $javaScript .= "\r\n";
   $javaScript .= "var priceGuideURL = \"http://lastplayerpicked.zxq.net/index.php?" . $queryString . "&o=S\"\r\n";
   $javaScript .= "var players = new Array();\r\n";
   $javaScript .= "\r\n";
   $javaScript .= "getPlayers();\r\n";
   $javaScript .= "\r\n";

   if ($leagueType == ESPN)
   {
      $javaScript .= "document.addEventListener(\"DOMNodeInserted\", getInfo, false);\r\n";
      $javaScript .= "\r\n";
      $javaScript .= "function getInfo(event)\r\n";
      $javaScript .= "{\r\n";
      $javaScript .= "   if (event.target.tagName == \"FORM\" || event.target.tagName == \"TABLE\")\r\n";
      $javaScript .= "   {\r\n";
      $javaScript .= "      showValues();\r\n";
      $javaScript .= "   }\r\n";
      $javaScript .= "}\r\n";
      $javaScript .= "\r\n";
   }
   elseif ($leagueType == CBS)
   {
      $javaScript .= "document.addEventListener(\"DOMNodeInserted\", getInfo, false);\r\n";
      $javaScript .= "\r\n";
      $javaScript .= "function getInfo(event)\r\n";
      $javaScript .= "{\r\n";
      $javaScript .= "   if (event.target.tagName == \"SPAN\" || event.target.tagName == \"TABLE\")\r\n";
      $javaScript .= "   {\r\n";
      $javaScript .= "      showValues();\r\n";
      $javaScript .= "   }\r\n";
      $javaScript .= "}\r\n";
      $javaScript .= "\r\n";
   }
   elseif ($leagueType == YAHOO)
   {
      $javaScript .= "document.addEventListener(\"DOMNodeInserted\", getInfo, false);\r\n";
      $javaScript .= "\r\n";
      $javaScript .= "function getInfo(event)\r\n";
      $javaScript .= "{\r\n";
      $javaScript .= "   if (event.target.tagName == \"DIV\")\r\n";
      $javaScript .= "   {\r\n";
      $javaScript .= "      showValues();\r\n";
      $javaScript .= "   }\r\n";
      $javaScript .= "}\r\n";
      $javaScript .= "\r\n";
   }
   $javaScript .= "function getPlayers()\r\n";
   $javaScript .= "{\r\n";
   $javaScript .= "GM_xmlhttpRequest(\r\n";
   $javaScript .= "{\r\n";
   $javaScript .= "    method: 'GET',\r\n";
   $javaScript .= "    url: priceGuideURL,\r\n";
   $javaScript .= "    onload: function( responseDetails )\r\n";
   $javaScript .= "    {\r\n";
   $javaScript .= "      buildPlayersHash(responseDetails.responseText);\r\n";
   $javaScript .= "      showValues();\r\n";
   $javaScript .= "    },\r\n";
   $javaScript .= "});\r\n";
   $javaScript .= "}\r\n";
   $javaScript .= "\r\n";
   $javaScript .= "function buildPlayersHash(playersCSV)\r\n";
   $javaScript .= "{\r\n";
   $javaScript .= "   var playerValues = playersCSV.split(\"\\n\");\r\n";
   $javaScript .= "\r\n";
   $javaScript .= "   for (var i = 0; i < playerValues.length - 1; i++)\r\n";
   $javaScript .= "   {\r\n";
   $javaScript .= "      var player = playerValues[i].split(\",\");\r\n";
   $javaScript .= "      var dollarValue = 0;\r\n";
   $javaScript .= "\r\n";
   $javaScript .= "      if (player[3] > 0)\r\n";
   $javaScript .= "      {\r\n";
   $javaScript .= "         dollarValue = \"$\" + Number(player[3]).toFixed(0);\r\n";
   $javaScript .= "      }\r\n";
   $javaScript .= "      else\r\n";
   $javaScript .= "      {\r\n";
   $javaScript .= "         dollarValue =  \"-$\" + Math.abs(Number(player[3]).toFixed(0));\r\n";
   $javaScript .= "      }\r\n";
   $javaScript .= "      players[ player[" . getLeagueIdIndex($leagueType) . "] ] = dollarValue;\r\n";
   $javaScript .= "   }\r\n";
   $javaScript .= "}\r\n";
   $javaScript .= "\r\n";
   $javaScript .= "function showValues()\r\n";
   $javaScript .= "{\r\n";
   if ($leagueType != ESPN)
   {
      $javaScript .= "   var playerMatch = /" . getLeagueMatch($leagueType) . "/;\r\n";
      $javaScript .= "\r\n";
   }
   $javaScript .= "   var tags = document.getElementsByTagName(\"a\");\r\n";
   $javaScript .= "\r\n";
   $javaScript .= "   for (var i = 0; i < tags.length; i++)\r\n";
   $javaScript .= "   {\r\n";
   if ($leagueType == ESPN)
   {
      $javaScript .= "      var isPlayer = tags[i].attributes.getNamedItem(\"playerid\") != null;\r\n";
      $javaScript .= "      var isBreakingNewsImage = tags[i].attributes.getNamedItem(\"tab\") != null && tags[i].attributes.getNamedItem(\"tab\").value != \"null\";\r\n";

      $javaScript .= "\r\n";
      $javaScript .= "      if ( isPlayer && !isBreakingNewsImage )\r\n";
      $javaScript .= "      {\r\n";
      $javaScript .= "         var playerID = tags[i].attributes.getNamedItem(\"playerid\").value;\r\n";
   }
   elseif ($leagueType == CBS)
   {
      $javaScript .= "      var isPlayer = tags[i].href.match(playerMatch);\r\n";
      $javaScript .= "      var isInjury = tags[i].attributes.getNamedItem(\"subtab\") != null;\r\n";

      $javaScript .= "\r\n";
      $javaScript .= "      if (isPlayer && !isInjury)\r\n";
      $javaScript .= "      {\r\n";
      $javaScript .= "         var playerID = isPlayer[1];\r\n";
   }
   else
   {
      $javaScript .= "      var isPlayer = tags[i].href.match(playerMatch);\r\n";
      $javaScript .= "\r\n";
      $javaScript .= "      if (isPlayer)\r\n";
      $javaScript .= "      {\r\n";
      $javaScript .= "         var playerID = isPlayer[1];\r\n";
   }
   $javaScript .= "         if (players[playerID] != null)\r\n";
   $javaScript .= "         {\r\n";
   $javaScript .= "            var dollarValue = players[playerID];\r\n";
   $javaScript .= "\r\n";
   $javaScript .= "            if (tags[i].innerHTML.indexOf(dollarValue) < 0)\r\n";
   $javaScript .= "            {\r\n";
   $javaScript .= "               tags[i].innerHTML = tags[i].innerHTML + \" \" + dollarValue;\r\n";
   $javaScript .= "            }\r\n";
   $javaScript .= "         }\r\n";
   $javaScript .= "      }\r\n";
   $javaScript .= "   }\r\n";
   $javaScript .= "}\r\n";
   $javaScript .= "\r\n";
   $javaScript .= "})();\r\n";
   
   return $javaScript;
}

?>