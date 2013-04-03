<?php

require_once "PlayersInput.php";
require_once "PlayersOutput.php";

require_once "Request.php";
require_once "Results.php";

require_once "PlayerLoader.php";
require_once "HTMLResultsFormatter.php";

buildStartHTML();

if ($_POST)
{
   buildResultsHTML($_POST["i"], $_POST["agree"]);
}
else
{
   buildFormHTML($_GET["i"], "");
}

buildEndHTML();

function buildResultsHTML($randomId, $agree)
{
   if (!isValidRandomId($randomId))
   {
      $randomId = getRandomId(8);
   }

   $hittersFileName  = "cusds/" . $randomId . "Batting.csv";
   $pitchersFileName = "cusds/" . $randomId . "Pitching.csv";

   if (!$agree)
   {
      $errorMessage = "Please check the box to indicate that you have permission to upload the files.";
   }

   if (!$errorMessage)
   {
      if (!preg_match("/\.csv\$/i", $_FILES["hitUp"]["name"]) || !preg_match("/\.csv\$/i", $_FILES["pitUp"]["name"]))
      {
         $errorMessage = "Both files must be valid CSV files.";
      }
   }

   if (!$errorMessage)
   {
      if (!move_uploaded_file($_FILES["hitUp"]["tmp_name"], $hittersFileName) || !move_uploaded_file($_FILES["pitUp"]["tmp_name"], $pitchersFileName))
      {
         $errorMessage = "Files upload failed.";
      }
   }

   if (!$errorMessage)
   {
      print "<div id=\"content\">";
      print "<div class=\"item_head_wide\">Dataset Upload</div>";
      print "<div class=\"item_wide\">";
      print "<p>Files uploaded!  Please verify the results below.</p>";
      print "<p>Continue to the <a href=\"index.php?cds=" . $randomId . "\">Rotisserie Price Guide</a>.</p>";
      print "<p>Continue to the <a href=\"points.php?cds=" . $randomId . "\">Points Price Guide</a>.</p>";
      print "</div>";
      print "</div>";
      
      buildStatsTable($randomId, $hittersFileName, $pitchersFileName);

   }
   else
   {
      buildFormHTML($randomId, $errorMessage);
   }
}

function buildStatsTable($randomId, $hittersFileName, $pitchersFileName)
{

   $results = new Results();
   $request = new Request();

   $request->dataset = $randomId;
   $request->customId = $randomId;
   $request->adjustPlayingTime = false;
   $request->hittersInput->positions["C"]    = 1;
   $request->hittersInput->positions["SS"]   = 1;
   $request->hittersInput->positions["2B"]   = 1;
   $request->hittersInput->positions["3B"]   = 1;
   $request->hittersInput->positions["OF"]   = 1;
   $request->hittersInput->positions["CF"]   = 1;
   $request->hittersInput->positions["LF"]   = 1;
   $request->hittersInput->positions["RF"]   = 1;
   $request->hittersInput->positions["1B"]   = 1;
   $request->hittersInput->positions["Util"] = 1;

   $request->hittersInput->minGames["C"]    = 20;
   $request->hittersInput->minGames["1B"]   = 20;
   $request->hittersInput->minGames["2B"]   = 20;
   $request->hittersInput->minGames["3B"]   = 20;
   $request->hittersInput->minGames["SS"]   = 20;
   $request->hittersInput->minGames["OF"]   = 20;
   $request->hittersInput->minGames["CF"]   = 20;
   $request->hittersInput->minGames["LF"]   = 20;
   $request->hittersInput->minGames["RF"]   = 20;
   $request->hittersInput->minGames["Util"] = 1;
   $request->hittersInput->categories = buildHittingCategories($hittersFileName);

   $request->pitchersInput->positions["SP"]  = 1;
   $request->pitchersInput->positions["RP"]  = 1;
   $request->pitchersInput->positions["P"]  = 1;

   $request->pitchersInput->minGames["SP"]   = 5;
   $request->pitchersInput->minGames["RP"]   = 5;
   $request->pitchersInput->minGames["P"]    = 1;
   $request->pitchersInput->categories = buildPitchingCategories($pitchersFileName);

   $loader = new PlayerLoader();
   $results->hitters = $loader->loadPlayers($request, "Hitters", false);
   $results->pitchers = $loader->loadPlayers($request, "Pitchers", false);

   $formatter = new HTMLResultsFormatter();
   $formatter->buildHTML($request, $results, 20, $queryString, true, false, false);
}

function buildFormHTML($randomId, $errorMessage)
{
   if (!isValidRandomId($randomId))
   {
      $randomId = getRandomId(8);
   }

?>
<div id="content">
<form enctype="multipart/form-data" method="post" action="upload.php">

<div class="item_head_wide">Dataset Upload</div>
<div class="item_wide">
<p class="error_text"><?php print $errorMessage ?></p>
<input type="hidden" name="MAX_FILE_SIZE" value="200000" />
<input type="hidden" name="i" value="<?php print $randomId ?>" />
<label class="wide">Hitters file:</label> <input name="hitUp" type="file" /><br class="clear" />
<label class="wide">Pitchers file:</label> <input name="pitUp" type="file" /><br class="clear" />
<label><span class="narrow"><input type="checkbox" name="agree" /></span>I have permission to reproduce and distribute the files I am uploading.</label><br />
</div>

<div class="container">
   <input type="submit" value="Upload Files" id="submit" />
</div>

<div class="item_head_wide">Instructions</div>
<div class="item_wide">
<p>To use your own set of projections, you will need to upload two CSV files: one for <strong>hitters</strong> and one for <strong>pitchers</strong>.
In Excel, you will need to choose "Save As" and select "CSV (Comma-delimited)" for the format to save a .csv.</p>
<p>The Price Guide will use the column headings from the first row of the spreadsheet to generate player values.</p>
<p>For <strong>hitters</strong>, the following columns are required:</p>
<p><strong>playerName</strong><br />
<strong>defaultPos</strong> (C, 1B, 2B, 3B, SS, OF, or blank)<br />
<strong>AB</strong><br />
<strong>H</strong><br />
<strong>R</strong><br />
<strong>RBI</strong><br />
<strong>SB</strong><br />
<strong>HR</strong></p>

<p>You need at least these columns for <strong>pitchers</strong>:</p>
<p><strong>playerName</strong><br />
<strong>defaultPos</strong> (SP, RP, or P)<br />
<strong>IP</strong><br />
<strong>ER</strong><br />
<strong>H</strong><br />
<strong>BB</strong><br />
<strong>W</strong><br />
<strong>S</strong><br />
<strong>K</strong> (not "SO")</p>
<p>Columns can appear in any order, but the headings are case-sensitive.</p>
<p>If you want to see examples of how your CSV's should look, check out the <a href="templates/BattingTemplate.csv">BattingTemplate.csv</a> and the <a href="templates/PitchingTemplate.csv">PitchingTemplate.csv</a>.</p>
</div>

</form>
</div>

<?php
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
<body class="roto" onload="showTable('hitters')">

<div class="extra_wide_top">
   <h1><a href="http://lastplayerpicked.zxq.net">Last Player Picked</a></h1>
</div>


<?php

}

function buildEndHTML()
{

?>

<div class="extra_wide_bottom">

   <p>Copyright 2011-2013 Mays Copeland.</p>

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

function isValidRandomId($randomId)
{
   if (strlen($randomId) != 8)
   {
      return false;
   }

   if (!preg_match("/^[a-z0-9]{8}$/", $randomId))
   {
      return false;
   }
   
   return true;

}


function buildHittingCategories($hittersFileName)
{
   $categories = array();
   $columns = getColumnHeadings($hittersFileName);

   foreach ($columns as $col)
   {
      switch($col)
      {
         case "HR":
         case "SB":
         case "R":
         case "RBI":
         case "H":
         case "AB":
         case "BB":
         case "HBP":
         case "SF":
         case "IBB":
         case "SO":
         case "CS":
         case "E":
         case "1B":
         case "2B":
         case "3B":
            $categories[] = $col;
            break;
      }
   }

   return $categories;
}

function buildPitchingCategories($pitchersFileName)
{
   $categories = array();
   $columns = getColumnHeadings($pitchersFileName);

   foreach ($columns as $col)
   {
      switch($col)
      {
         case "W":
         case "L":
         case "S":
         case "K":
         case "BB":
         case "ER":
         case "QS":
         case "HLD":
         case "HR":
         case "IP":
            $categories[] = $col;
            break;
      }
   }

   return $categories;
}

function getColumnHeadings($fileName)
{
   $columns = array();

   if (file_exists($fileName))
   {
      $handle = fopen($fileName, "r");
   }
   if ($handle)
   {
      if (!feof($handle))
      {
         $columns = fgetcsv($handle);
      }

      fclose($handle);
   }
   return $columns;
   
}
?>