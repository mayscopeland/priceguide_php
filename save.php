<?php

if ($_GET)
{
	$values = array();

	$randomId = $_GET["i"];

   if (isValidRandomId($randomId))
   {
   	foreach ($_GET as $key => $value)
   	{
   		if ($key != "i")
   		{
   			$values[$key] = $value;
   		}
   	}

   	$handle = fopen("../../cuspl/" . $randomId, "a");

   	if ($handle)
   	{
   		foreach ($values as $key => $value)
   		{
   			fwrite($handle, $key . "=" . $value . ",");
   		}

   		fwrite($handle, "\n");

   		fclose($handle);
   	}
	}
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

?>