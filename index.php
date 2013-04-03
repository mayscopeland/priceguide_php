<?php

require_once "PlayersInput.php";
require_once "PlayersOutput.php";

require_once "Request.php";
require_once "Results.php";

require_once "PlayerLoader.php";
require_once "ValuesBuilder.php";
require_once "DollarBuilder.php";
require_once "CSVResultsFormatter.php";
require_once "HTMLResultsFormatter.php";
require_once "ProgressUpdater.php";

if (!needToLoadForm())
{
   $debugMode = ($_GET["debug"] == "Y");
   $numberOfPlayersDisplayed = $_GET["dis"];
   $customizeProjections = ($_GET["i"] || $_GET["k"]);
   $inTest = ($_SERVER["SERVER_ADDR"] == "127.0.0.1");
   $queryString = $_SERVER["QUERY_STRING"];
                                                                      
   // No caching while on zxq.net
   $caching = false;

   $request = buildRequest();

   if (!$request->outputAsCSV && !$request->outputAsSimpleCSV)
   {
      buildStartHTML();
      buildProgressHTML();
   }
   
   $cacheFileName = "cache/" . md5(serialize($request)) . ".cache";

   if (file_exists($cacheFileName))
   {
      // Load the cached results
      $results = unserialize(file_get_contents($cacheFileName));
   }
   else
   {
      if ($request->outputAsCSV || $request->outputAsSimpleCSV)
      {
         $results = processRequest($request, false);
      }
      else
      {
         $results = processRequest($request, true);
      }

      // Cache the results
      if (!$customizeProjections && !$inTest && $caching)
      {
         file_put_contents($cacheFileName, serialize($results));
      }
   }

   if ($request->outputAsCSV || $request->outputAsSimpleCSV)
   {
      $formatter = new CSVResultsFormatter();
      $formatter->buildCSV($request, $results, $request->outputAsSimpleCSV, false);
   }
   else
   {
      $progress = new ProgressUpdater();
      $progress->finishProgress(true);

      // Format up the results
      $formatter = new HTMLResultsFormatter();
      $formatter->buildHTML($request, $results, $numberOfPlayersDisplayed, $queryString, false, false, $debugMode);
      buildEndHTML();
   }

}
else
{
   $customDataset = $_GET["cds"];

   buildStartHTML();
   buildFormHTML($customDataset);
   buildEndHTML();
}

function needToLoadForm()
{
   // Just uploaded projections...
   if (($_GET) && ($_GET["cds"]))
   {
      return true;
   }

   // Coming to the page with nothing...
   if (!$_GET)
   {
      return true;
   }  

   // Price Guide form has been submitted
   return false;
}

function getRandomId($length)
{

   $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
   $randomId = "";

   for ($i = 0; $i < $length; $i++)
   {
      $randomId = $randomId . substr($chars, rand(0, strlen($chars) - 1), 1);
   }

   return $randomId;

}

function buildHittingCategories()
{
   $categories = array();

   foreach ($_GET as $key => $value)
   {
      if ($value == "Y")
      {
         switch($key)
         {
            case "HR":
            case "SB":
            case "R":
            case "RBI":
            case "H":
            case "BB":
            case "TB":
            case "HBP":
            case "SH":
            case "SF":
            case "IBB":
            case "SO":
            case "SB-CS":
            case "E":
            case "GIDP":
            case "A":
               $categories[] = $key;
               break;
            case "AVG":
            case "OBP":
            case "SLG":
            case "OPS":
            case "SB%":
            case "KAB":
               $categories[] = "x" . $key;
               break;
            case "SI":
               $categories[] = "1B";
               break;
            case "DB":
               $categories[] = "2B";
               break;
            case "TP":
               $categories[] = "3B";
               break;
            case "2B3B":
               $categories[] = "2B+3B";
               break;
            case "RuP":
               $categories[] = "RP";
               break;
         }
      }
   }

   return $categories;
}

function buildPitchingCategories()
{
   $categories = array();

   foreach ($_GET as $key => $value)
   {
      if ($value == "Y")
      {
         switch($key)
         {
            case "W":
            case "S":
            case "K":
            case "K-BB":
            case "W-L":
            case "2W-L":
            case "L":
            case "QS":
            case "HLD":
            case "IP":
            case "G":
               $categories[] = $key;
               break;
            case "ERA":
            case "WHIP":
            case "K9":
            case "BB9":
            case "KBB":
            case "HR9":
            case "W%":
            case "BAA":
               $categories[] = "x" . $key;
               break;
            case "PBB":
               $categories[] = "BB";
               break;
            case "PH":
               $categories[] = "H";
               break;
            case "PHR":
               $categories[] = "HR";
               break;
            case "SHLD":
               $categories[] = "S+HLD";
               break;
         }
      }
   }

   return $categories;
}

function buildRequest()
{
   $request = new Request();

   $request->dataset       = $_GET["ds"];
   $request->numberOfTeams = $_GET["t"];
   $request->moneyPerTeam  = $_GET["m"];
   $request->league        = $_GET["l"];
   $request->minimumBid    = $_GET["b"];
   $request->customId      = $_GET["i"];
   $request->keeperId      = $_GET["k"];
   //$request->keeperId      = $_GET["u"];
   $request->outputAsCSV       = ($_GET["o"] == "CSV");
   $request->outputAsSimpleCSV = ($_GET["o"] == "S");
   
   if ($request->minimumBid == 0)
   {
      $request->minimumBid = 1;
   }

   $request->useTopPosition = ($_GET["tp"] == "Y");
   $request->adjustPlayingTime = ($_GET["pt"] == "Y");
   $request->useCustomSplit = ($_GET["spl"] == "Y");

   if ($request->useCustomSplit)
   {
      $request->hittersSplit = $_GET["hs"] / 100;
      $request->pitchersSplit = $_GET["ps"] / 100;
   }
   
   $request->updatedProjections = ($_GET["u"] == "U");
   $request->restOfSeasonProjections = ($_GET["u"] == "R");

   // These need to be in descending order of value.
   $request->hittersInput->positions["C"]    = $_GET["C"] * $request->numberOfTeams;
   $request->hittersInput->positions["SS"]   = $_GET["SS"] * $request->numberOfTeams;
   $request->hittersInput->positions["2B"]   = $_GET["2B"] * $request->numberOfTeams;
   $request->hittersInput->positions["3B"]   = $_GET["3B"] * $request->numberOfTeams;
   $request->hittersInput->positions["CF"]   = $_GET["CF"] * $request->numberOfTeams;
   $request->hittersInput->positions["LF"]   = $_GET["LF"] * $request->numberOfTeams;
   $request->hittersInput->positions["RF"]   = $_GET["RF"] * $request->numberOfTeams;
   $request->hittersInput->positions["OF"]   = $_GET["OF"] * $request->numberOfTeams;
   $request->hittersInput->positions["1B"]   = $_GET["1B"] * $request->numberOfTeams;
   $request->hittersInput->positions["MI"]   = $_GET["MI"] * $request->numberOfTeams;
   $request->hittersInput->positions["CI"]   = $_GET["CI"] * $request->numberOfTeams;
   $request->hittersInput->positions["IF"]   = $_GET["IF"] * $request->numberOfTeams;
   $request->hittersInput->positions["Util"] = $_GET["Util"] * $request->numberOfTeams;

   $request->hittersInput->minGames["C"]    = $_GET["mg"];
   $request->hittersInput->minGames["1B"]   = $_GET["mg"];
   $request->hittersInput->minGames["2B"]   = $_GET["mg"];
   $request->hittersInput->minGames["3B"]   = $_GET["mg"];
   $request->hittersInput->minGames["SS"]   = $_GET["mg"];
   $request->hittersInput->minGames["OF"]   = $_GET["mg"];
   $request->hittersInput->minGames["LF"]   = $_GET["mg"];
   $request->hittersInput->minGames["CF"]   = $_GET["mg"];
   $request->hittersInput->minGames["RF"]   = $_GET["mg"];
   $request->hittersInput->minGames["CI"]   = $_GET["mg"];
   $request->hittersInput->minGames["MI"]   = $_GET["mg"];
   $request->hittersInput->minGames["IF"]   = $_GET["mg"];
   $request->hittersInput->minGames["Util"] = 1;

   $request->hittersInput->categories = buildHittingCategories();
   $request->hittersInput->numberOfPlayersDrafted  = array_sum($request->hittersInput->positions);

   $request->pitchersInput->positions["SP"]  = $_GET["SP"] * $request->numberOfTeams;
   $request->pitchersInput->positions["RP"]  = $_GET["RP"] * $request->numberOfTeams;
   $request->pitchersInput->positions["P"]   = $_GET["P"] * $request->numberOfTeams;

   $request->pitchersInput->minGames["SP"]   = $_GET["ms"];
   $request->pitchersInput->minGames["RP"]   = $_GET["mr"];
   $request->pitchersInput->minGames["P"]    = 1;

   $request->pitchersInput->categories = buildPitchingCategories();
   $request->pitchersInput->numberOfPlayersDrafted = array_sum($request->pitchersInput->positions);

   return $request;
}

function processRequest($request, $isHTMLOutput)
{
   $results = new Results();
   $progress = new ProgressUpdater();

   // Load the players from file
   $loader = new PlayerLoader();
   $results->hitters = $loader->loadPlayers($request, "Hitters", $isHTMLOutput);
   $results->pitchers = $loader->loadPlayers($request, "Pitchers", $isHTMLOutput);

   $progress->updateProgress("Building values (Hitters)...", $isHTMLOutput);

   // Build the values for hitters
   $builder = new ValuesBuilder();
   $results->hittersOutput = $builder->buildValues($results->hitters, $request->hittersInput);

   $progress->updateProgress("Building values (Pitchers)...", $isHTMLOutput);

   // Build the values for pitchers
   $builder = new ValuesBuilder();
   $results->pitchersOutput = $builder->buildValues($results->pitchers, $request->pitchersInput);

   $results->totalIP = 0;
   for ($i = 0; $i < $request->pitchersInput->numberOfPlayersDrafted; $i++)
   {
      if ($results->pitchers[$i]["IP"] > 0)
      {
         $results->totalIP += $results->pitchers[$i]["IP"];
      }
   }
   if ($request->numberOfTeams > 0)
   {
      $results->totalIP /= $request->numberOfTeams;
   }

   $progress->updateProgress("Generating dollar values...", $isHTMLOutput);

   // Come up with some dollar values
   $builder = new DollarBuilder();
   $builder->buildDollars($request, $results);

   return $results;
}

function buildStartHTML()
{
   
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>The Fantasy Baseball Price Guide - Last Player Picked</title>

<meta name="description" content="The Fantasy Baseball Price Guide is a tool that generates custom fantasy baseball dollar values for a vast array of league configurations." />
<meta name="keywords" content="fantasy, baseball, auction, draft, dollar values, 2009, player rater" />

<link rel="stylesheet" type="text/css" href="style/pg.css" />
<script type="text/javascript" src="js/pg.js"></script>

</head>
<body class="roto" onload="showTable('hitters');">

<div class="extra_wide_top">
   <h1><a href="http://lastplayerpicked.zxq.net">Last Player Picked</a></h1>
</div>

<?php

}

function buildFormHTML($customDataset)
{

   if ($customDataset)
   {
      $i = $customDataset;
   }
   else
   {
      $i = getRandomId(8);
   }

?>

<div id="content">

<div class="item_head_wide">The Fantasy Baseball Price Guide</div>
<div class="item_wide">
<p>The Fantasy Baseball Price Guide is a tool that generates custom dollar values for a vast array of
league configurations.</p>
<p>If you are in a <strong>points</strong> league, there is a separate <a href="points.php">Points League</a> version of the Price Guide that can handle your league setup.</p>

<div align="center">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div>

<form method="get" action="index.php">

</div>

<div class="item_head_wide">League Settings (Roto)</div>
<div class="item_wide">
<label class="wide">Preset:</label>
<select onchange="updateForm(this.options[selectedIndex].value)">
   <option value="S">Standard Roto</option>
   <option value="E">ESPN</option>
   <option value="Y">Yahoo!</option>
   <option value="C" selected="selected">Custom</option>
</select><br class="clear" />
<label class="wide">Number of teams:</label><input type="text" id="t" name="t" value="12" size="2" /><br class="clear" />
<label class="wide">League:</label>
<select id="l" name="l">
   <option value="MLB" selected="selected">MLB</option>
   <option value="AL">AL</option>
   <option value="NL">NL</option>
</select><br class="clear" />
<label class="wide">$ per team:</label><input type="text" id="m" name="m" value="260" size="3" /><br class="clear" />
<label class="wide">Minimum bid:</label><input type="text" id="b" name="b" value="1" size="3" /><br class="clear" />
<label class="wide">Data source:</label>
<select name="ds">
<?php
   if ($customDataset)
   {
      print "<option value=\"" . $customDataset . "\" selected=\"selected\">Custom Dataset</option>";
      print "<option value=\"13S\">2013 Stats</option>";
   }
   else
   {
      print "<option value=\"13S\" selected=\"selected\">2013 Stats</option>";
   }
?>                                      
   <option value="134">2013 Composite</option>
   <option value="13C">2013 CAIRO</option>
   <option value="13E">2013 Steamer</option>
   <option value="12S">2012 Stats</option>
   <option value="124">2012 Composite</option>
   <option value="12C">2012 CAIRO</option>
   <option value="12E">2012 Steamer</option>
   <option value="11S">2011 Stats</option>
   <option value="114">2011 Composite</option>
   <option value="11C">2011 CAIRO</option>
   <option value="11M">2011 Marcel</option>
   <option value="11E">2011 Steamer</option>
   <option value="11Z">2011 ZiPS</option>
   <option value="10S">2010 Stats</option>
   <option value="10F">2010 Composite</option>
   <option value="10C">2010 CAIRO</option>
   <option value="10H">2010 CHONE</option>
   <option value="10M">2010 Marcel</option>
   <option value="10E">2010 Steamer</option>
   <option value="10Z">2010 ZiPS</option>
   <option value="09S">2009 Stats</option>
   <option value="09A">2009 Composite</option>
   <option value="09C">2009 CAIRO</option>
   <option value="09H">2009 CHONE</option>
   <option value="09M">2009 Marcel</option>
   <option value="09Z">2009 ZiPS</option>
   <option value="08S">2008 Stats</option>
</select><br class="clear" />
<label class="wide">Players displayed:</label><input type="text" name="dis" value="250" size="3" /><br class="clear" />
<label><span class="narrow"><input type="radio" name="spl" value="" checked="checked" /></span>Use "optimal" hitter/pitcher split</label><br class="clear"/>
<label><span class="narrow"><input type="radio" name="spl" value="Y" /></span>Use <input type="text" name="hs" value="70" size="2" /> / <input type="text" name="ps" value="30" size="2" /> split</label><br class="clear"/>
<label><span class="narrow"><input type="checkbox" name="pt" value="Y" /></span> Adjust for playing time and save opps.</label><br class="clear"/>
<label><span class="narrow"><input type="checkbox" name="k" value="<?php print $i ?>" /></span> Let me enter keepers</label><br class="clear"/>
<label><span class="narrow"><input type="checkbox" name="i" value="<?php print $i ?>" /></span> Let me customize these projections</label><br />
<label><a href="upload.php">Let me upload my own projections</a></label><br class="clear"/>
<!-- 
<label><span class="narrow"><input type="radio" name="u" value="" checked="checked" /></span>Use preseason projection</label><br class="clear"/>
<label><span class="narrow"><input type="radio" name="u" value="R" /></span>Update projection with current season stats</label><br class="clear"/>
<label><span class="narrow"><input type="radio" name="u" value="U" /></span>Use rest-of-season projection</label><br class="clear"/>
-->

</div>

<div class="container">

<div id="hitter_stats">
   <div class="item_head">Batting Categories</div>
   <div class="item">
      <label><span class="narrow"><input type="checkbox" name="AVG" value="Y" checked="checked" /></span> AVG</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="R" value="Y" checked="checked" /></span> R</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="RBI" value="Y" checked="checked" /></span> RBI</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="HR" value="Y" checked="checked" /></span> HR</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="SB" value="Y" checked="checked" /></span> SB</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="OBP" value="Y" /></span> OBP</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="SLG" value="Y" /></span> SLG</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="OPS" value="Y" /></span> OPS</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="H" value="Y" /></span> H</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="BB" value="Y" /></span> BB</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="SI" value="Y" /></span> 1B</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="DB" value="Y" /></span> 2B</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="TP" value="Y" /></span> 3B</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="2B3B" value="Y" /></span> 2B + 3B</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="TB" value="Y" /></span> TB</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="SB%" value="Y" /></span> SB%</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="SB-CS" value="Y" /></span> SB - CS</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="RuP" value="Y" /></span> RP (R + RBI - HR)</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="SO" value="Y" /></span> K</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="KAB" value="Y" /></span> K/AB</label><br class="clear"/> 
      <label><span class="narrow"><input type="checkbox" name="GIDP" value="Y" /></span> GIDP</label><br class="clear"/>
<?php
   if ($customDataset)
   {
      print "<label><span class=\"narrow\"><input type=\"checkbox\" name=\"E\" value=\"Y\" /></span> Errors</label><br class=\"clear\"/>";
      print "<label><span class=\"narrow\"><input type=\"checkbox\" name=\"A\" value=\"Y\" /></span> Assists</label><br class=\"clear\"/>";
   }
?>
   </div>
</div>

<div id="pitcher_stats">
   <div class="item_head">Pitching Categories</div>
   <div class="item">
      <label><span class="narrow"><input type="checkbox" name="W" value="Y" checked="checked" /></span> W</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="S" value="Y" checked="checked" /></span> S</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="ERA" value="Y" checked="checked" /></span> ERA</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="WHIP" value="Y" checked="checked" /></span> WHIP</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="K" value="Y" checked="checked" /></span> K</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="PBB" value="Y" /></span> BB</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="PH" value="Y" /></span> H</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="PHR" value="Y" /></span> HR</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="W-L" value="Y" /></span> W - L</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="2W-L" value="Y" /></span> 2W - L</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="L" value="Y" /></span> L</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="K9" value="Y" /></span> K/9</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="KBB" value="Y" /></span> K/BB</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="BB9" value="Y" /></span> BB/9</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="HR9" value="Y" /></span> HR/9</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="W%" value="Y" /></span> W%</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="IP" value="Y" /></span> IP</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="K-BB" value="Y" /></span> K - BB</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="BAA" value="Y" /></span> BAA</label><br class="clear"/>
      <label><span class="narrow"><input type="checkbox" name="G" value="Y" /></span> G</label><br class="clear"/>
<?php
   if ($customDataset)
   {
      print "<label><span class=\"narrow\"><input type=\"checkbox\" name=\"QS\" value=\"Y\" /></span> QS</label><br class=\"clear\"/>";
      print "<label><span class=\"narrow\"><input type=\"checkbox\" name=\"HLD\" value=\"Y\" /></span> HLD</label><br class=\"clear\"/>";
      print "<label><span class=\"narrow\"><input type=\"checkbox\" name=\"SHLD\" value=\"Y\" /></span> S + HLD</label><br class=\"clear\"/>";
   }
?>
   </div>
</div>

</div>

<div class="container">

<div id="hitter_pos">
   <div class="item_head">Batting Positions</div>
   <div class="item">
      <span class="narrow"><input type="text" id="C" name="C" value="2" size="1" /></span> C<br class="clear"/>
      <span class="narrow"><input type="text" id="1B" name="1B" value="1" size="1" /></span> 1B<br class="clear"/>
      <span class="narrow"><input type="text" id="2B" name="2B" value="1" size="1" /></span> 2B<br class="clear"/>
      <span class="narrow"><input type="text" id="3B" name="3B" value="1" size="1" /></span> 3B<br class="clear"/>
      <span class="narrow"><input type="text" id="SS" name="SS" value="1" size="1" /></span> SS<br class="clear"/>
      <span class="narrow"><input type="text" id="OF" name="OF" value="5" size="1" /></span> OF<br class="clear"/>
      <span class="narrow"><input type="text" id="LF" name="LF" value="0" size="1" /></span> LF<br class="clear"/>
      <span class="narrow"><input type="text" id="CF" name="CF" value="0" size="1" /></span> CF<br class="clear"/>
      <span class="narrow"><input type="text" id="RF" name="RF" value="0" size="1" /></span> RF<br class="clear"/>
      <span class="narrow"><input type="text" id="CI" name="CI" value="1" size="1" /></span> CI<br class="clear"/>
      <span class="narrow"><input type="text" id="MI" name="MI" value="1" size="1" /></span> MI<br class="clear"/>
      <span class="narrow"><input type="text" id="IF" name="IF" value="0" size="1" /></span> IF<br class="clear"/>
      <span class="narrow"><input type="text" id="Util" name="Util" value="1" size="1" /></span> Util<br class="clear"/>
      <span class="narrow"><input type="text" id="mg" name="mg" value="20" size="1" /></span> games to qualify at a position<br class="clear"/>
   </div>
</div>

<div id="pitcher_pos">
   <div class="item_head">Pitching Positions</div>
   <div class="item">
      <span class="narrow"><input type="text" id="SP" name="SP" value="6" size="1" /></span> SP<br class="clear"/>
      <span class="narrow"><input type="text" id="RP" name="RP" value="3" size="1" /></span> RP<br class="clear"/>
      <span class="narrow"><input type="text" id="P" name="P" value="0" size="1" /></span> P<br class="clear"/>
      <span class="narrow"><input type="text" id="ms" name="ms" value="5" size="1" /></span> starts to qualify as SP<br class="clear"/>
      <span class="narrow"><input type="text" id="mr" name="mr" value="5" size="1" /></span> relief appearances to qualify as RP<br class="clear"/>
   </div>
</div>

</div>

<div class="container">
   <input type="submit" value="Get Values" id="submit" />
</div>

</form>
</div>

<?php

}

function buildEndHTML()
{
   
?>

<div class="extra_wide_bottom">

   <p>Copyright 2008-2013 Mays Copeland.</p>

</div>

<script type="text/javascript">
   var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
   document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>

<script type="text/javascript">
   var pageTracker = _gat._getTracker("UA-6651480-1");
   pageTracker._trackPageview();
</script>


</body>
</html>

<?php

}

function buildProgressHTML()
{
   
?>

<div id="progress" class="extra_wide_top">Starting build process...</div>

<?php

    ob_end_flush();
    ob_flush();
    flush();
    ob_start();
}