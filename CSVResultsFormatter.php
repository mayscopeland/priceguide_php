<?php

require_once "Request.php";
require_once "Results.php";

require_once "PlayersInput.php";
require_once "PlayersOutput.php";

class CSVResultsFormatter
{
   private $pointsLeague = false;

   public function buildCSV($request, $results, $outputSimpleCSV, $pointsLeague)
   {
      $this->pointsLeague = $pointsLeague;
      
      header("Content-type: text/csv");
      $fileName = $this->buildCSVFileName($request);
      header('Content-Disposition: attachment; filename="' . $fileName . '"');
      
      $outstream = fopen("php://output",'w');
      
      fwrite($outstream, $this->buildPlayerLines($request, $results, $request->outputAsSimpleCSV));

      fclose($outstream);
   }

   private function buildCSVFileName($request)
   {
      $filename = "";
      
      $fileName .= $request->numberOfTeams . "TM-";
      $fileName .= $request->league . "-";
      if (!$this->pointsLeague)
      {
         $fileName .= count($request->hittersInput->categories) . "x" . count($request->pitchersInput->categories);
      }
      else
      {
         $fileName .= "PTS";
      }
      $fileName .= ".csv";
      
      return $fileName;
   }

   private function addToArray($origArray, $addedArray)
   {
      foreach($addedArray as $newItem)
      {
         if (!in_array($newItem, $origArray))
         {
            $origArray[] = $newItem;
         }
      }
      return $origArray;
   }

   private function addKeysToArray($origArray, $addedArray)
   {
      foreach($addedArray as $newItem => $value)
      {
         if (!in_array($newItem, $origArray))
         {
            $origArray[] = $newItem;
         }
      }
      return $origArray;
   }

   private function buildPlayerLines($request, $results, $outputSimpleCSV)
   {
      if (!$outputSimpleCSV)
      {
         $cols = array("mlbamID", "statsID", "cbsID", "espnID", "playerName", "team", "league", "pos", "total", "adjTotal", "dollarValue", "adjustedDollarValue", "keeperPrice");
         if (!$this->pointsLeague)
         {
            $cols = $this->addToArray($cols, $request->hittersInput->categories);
            $cols = $this->addToArray($cols, $results->hittersOutput->components);
            foreach($request->hittersInput->categories as $category)
            {
               $cols[] = "m" . $category;
            }
            $cols = $this->addToArray($cols, $request->pitchersInput->categories);
            $cols = $this->addToArray($cols, $results->pitchersOutput->components);
            foreach($request->pitchersInput->categories as $category)
            {
               $cols[] = "m" . $category;
            }
         }
         else
         {
            $cols = $this->addKeysToArray($cols, $request->hittersInput->categories);
            $cols = $this->addKeysToArray($cols, $request->pitchersInput->categories);
         }

         foreach($cols as $col)
         {
            $lines .= $col . ",";
         }
         $lines .= "\n";
      }
      else
      {
         $cols = array("statsID", "cbsID", "espnID", "dollarValue");
      }
   
      foreach($results->hitters as $player)
      {
         foreach($cols as $col)
         {
            if ($col == "pos" && is_array($player["pos"]))
            {
               foreach($player["pos"] as $pos)
               {
                  $lines .= $pos . "-";
               }
               $lines[strlen($lines) - 1] = ",";
            }
            else
            {
               $lines .= $player[$col] . ",";
            }
         }
         $lines .= "\n";
      }
      foreach($results->pitchers as $player)
      {
         foreach($cols as $col)
         {
            if ($col == "pos" && is_array($player["pos"]))
            {
               foreach($player["pos"] as $pos)
               {
                  $lines .= $pos . "-";
               }
               $lines[strlen($lines) - 1] = ",";
            }
            else
            {
               $lines .= $player[$col] . ",";
            }
         }
         $lines .= "\n";
      }
      $lines .= "\n";
      
      return $lines;
   }
}

?>