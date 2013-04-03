<?php

class ProgressUpdater
{

   public function updateProgress($newText, $isHTMLOutput)
   {
      if ($isHTMLOutput)
      {
         print "<script type='text/javascript' language='javascript'>";
         print "updateProgress('" . $newText . "');";
         print "</script>";
         ob_end_flush();
         ob_flush();
         flush();
         ob_start();
      }
   }
   
   public function finishProgress($isHTMLOutput)
   {
      if ($isHTMLOutput)
      {
         print "<script type='text/javascript' language='javascript'>";
         print "finishProgress();";
         print "</script>";
         ob_end_flush();
         ob_flush();
         flush();
         ob_start();
      }
   }
}

?>