function updateForm(ruleSet)
{

   switch (ruleSet)
   {
      case "S":
         document.getElementById("t").value = 12;
         document.getElementById("C").value = 2;
         document.getElementById("1B").value = 1;
         document.getElementById("2B").value = 1;
         document.getElementById("3B").value = 1;
         document.getElementById("SS").value = 1;
         document.getElementById("OF").value = 5;
         document.getElementById("LF").value = 0;
         document.getElementById("CF").value = 0;
         document.getElementById("RF").value = 0;
         document.getElementById("MI").value = 1;
         document.getElementById("CI").value = 1;
         document.getElementById("IF").value = 0;
         document.getElementById("Util").value = 1;
         document.getElementById("mg").value = 20;
         document.getElementById("SP").value = 6;
         document.getElementById("RP").value = 3;
         document.getElementById("P").value = 0;
         document.getElementById("ms").value = 5;
         document.getElementById("mr").value = 5;
         document.getElementById("l").value = "MLB";
         break;
      case "E":
         document.getElementById("t").value = 10;
         document.getElementById("C").value = 1;
         document.getElementById("1B").value = 1;
         document.getElementById("2B").value = 1;
         document.getElementById("3B").value = 1;
         document.getElementById("SS").value = 1;
         document.getElementById("OF").value = 5;
         document.getElementById("LF").value = 0;
         document.getElementById("CF").value = 0;
         document.getElementById("RF").value = 0;
         document.getElementById("MI").value = 0;
         document.getElementById("CI").value = 0;
         document.getElementById("IF").value = 0;
         document.getElementById("Util").value = 1;
         document.getElementById("mg").value = 5;
         document.getElementById("SP").value = 6;
         document.getElementById("RP").value = 3;
         document.getElementById("P").value = 0;
         document.getElementById("ms").value = 5;
         document.getElementById("mr").value = 5;
         document.getElementById("l").value = "MLB";
         break;
      case "Y":
         document.getElementById("t").value = 12;
         document.getElementById("C").value = 1;
         document.getElementById("1B").value = 1;
         document.getElementById("2B").value = 1;
         document.getElementById("3B").value = 1;
         document.getElementById("SS").value = 1;
         document.getElementById("OF").value = 3;
         document.getElementById("LF").value = 0;
         document.getElementById("CF").value = 0;
         document.getElementById("RF").value = 0;
         document.getElementById("MI").value = 0;
         document.getElementById("CI").value = 0;
         document.getElementById("IF").value = 0;
         document.getElementById("Util").value = 2;
         document.getElementById("mg").value = 5;
         document.getElementById("SP").value = 2;
         document.getElementById("RP").value = 2;
         document.getElementById("P").value = 4;
         document.getElementById("ms").value = 5;
         document.getElementById("mr").value = 5;
         document.getElementById("l").value = "MLB";
         break;
   }
}

function showTable(tableToShow)
{
   switch (tableToShow)
   {
      // On the results page
      case "hitters":
         if (document.getElementById("hitters")) document.getElementById("hitters").style.display = "";
         if (document.getElementById("pitchers")) document.getElementById("pitchers").style.display = "none";
         if (document.getElementById("info")) document.getElementById("info").style.display = "none";
         setFrontDisplay(document.getElementById("hittersTab"));
         setBackDisplay(document.getElementById("pitchersTab"));
         setBackDisplay(document.getElementById("infoTab"));
         break;

      case "pitchers":
         if (document.getElementById("pitchers")) document.getElementById("pitchers").style.display = "";
         if (document.getElementById("hitters")) document.getElementById("hitters").style.display = "none";
         if (document.getElementById("info")) document.getElementById("info").style.display = "none";
         setFrontDisplay(document.getElementById("pitchersTab"));
         setBackDisplay(document.getElementById("hittersTab"));
         setBackDisplay(document.getElementById("infoTab"));
         break;

      case "info":
         if (document.getElementById("info")) document.getElementById("info").style.display = "";
         if (document.getElementById("hitters")) document.getElementById("hitters").style.display = "none";
         if (document.getElementById("pitchers")) document.getElementById("pitchers").style.display = "none";
         setFrontDisplay(document.getElementById("infoTab"));
         setBackDisplay(document.getElementById("hittersTab"));
         setBackDisplay(document.getElementById("pitchersTab"));
         break;
   }
}

function setFrontDisplay(tab)
{
   if (tab)
   {
      tab.style.paddingBottom = "6px";
      tab.style.borderBottomWidth = "0px";
      tab.style.backgroundColor = "#ffffff";
      tab.style.cursor = "";
   }
}

function setBackDisplay(tab)
{
   if (tab)
   {
      tab.style.paddingBottom = "0px";
      tab.style.borderBottomWidth = "6px";
      tab.style.backgroundColor = "#999999";
      tab.style.cursor = "pointer";
   }
}

function disableAll(item)
{
   if (item)
   {
      item.disabled = true;
      if (item.childNodes && item.childNodes.length > 0)
      {
         for (var x = 0; x < item.childNodes.length; x++)
         {
            disableAll(item.childNodes[x]);
         }
      }
   }
}

function enableAll(item)
{
   if (item)
   {
      item.disabled = false;
      if (item.childNodes && item.childNodes.length > 0)
      {
         for (var x = 0; x < item.childNodes.length; x++)
         {
            enableAll(item.childNodes[x]);
         }
      }
   }
}

function filterRows(pos, table)
{
   var alternateRow = false;

   for (var i = table.rows.length - 1; i > 0; i--)
   {
      if ((table.rows[i].cells[0].id == "hittersEdit")  || (table.rows[i].cells[0].id == "pitchersEdit"))
      {
         table.deleteRow(i);
      }
   }

   for (var i = 1; i < table.rows.length; i++)
   {
      var posString = table.rows[i].cells[2].innerHTML;

      if (searchPosString(pos, posString))
      {
         table.rows[i].style.display = "";

         if (alternateRow)
         {
            table.rows[i].style.backgroundColor = "#cccccc";
         }
         else
         {
            table.rows[i].style.backgroundColor = "#ffffff";
         }
         alternateRow = (!alternateRow);
      }
      else
      {
         table.rows[i].style.display = "none";
      }
   }

}

function searchPosString(pos, posString)
{
   switch (pos)
   {
      case " ":
         return posString == "&nbsp;";

      case "All":
         return true;

      case "P":
         return pos == posString;

      case "C":
         return (posString.match("C")) && (!posString.match("CF"));
         
      case "MI":
         return posString.match("2B") || posString.match("SS");

      case "CI":
         return posString.match("1B") || posString.match("3B");

      case "OF":
         return posString.match("OF") || posString.match("LF") || posString.match("CF") || posString.match("RF");

      default:
         return posString.match(pos);
   }

   return false;
}

function playerEdit(playerRow, tableID)
{
   var editRow = playerRow.parentNode.insertRow(playerRow.sectionRowIndex + 1);
   var editCell = document.getElementById(tableID).cloneNode(true);

   editRow.replaceChild(editCell, editRow.insertCell(0));

   var inputs = editCell.getElementsByTagName("input");

   for (j = 0; j < inputs.length; j++)
   {
      for (i = 0; i < playerRow.cells.length; i++)
      {
         if (inputs[j].name == playerRow.cells[i].id)
         {
            inputs[j].value = playerRow.cells[i].innerHTML.replace("$", "").replace("&nbsp;", "");
            inputs[j].focus();
         }
      }
   }
   
   var mlbamID = document.getElementById("mlbamID");
   mlbamID.value = playerRow.firstChild.firstChild.name;
   mlbamID.value = mlbamID.value.substr(1);
   
   var saveLink = document.getElementById("saveLink");
   saveLink.href = playerRow.firstChild.firstChild.name
}

function closeEdit(playerRow)
{
   playerRow.parentNode.deleteRow(playerRow.sectionRowIndex + 1);
}

function hittersEdit(tag)
{
   if (toggleEditText(tag))
   {
      playerEdit(tag.parentNode.parentNode, "hittersEdit");
   }
   else
   {
      closeEdit(tag.parentNode.parentNode);
   }
}

function pitchersEdit(tag)
{
   if (toggleEditText(tag))
   {
      playerEdit(tag.parentNode.parentNode, "pitchersEdit");
   }
   else
   {
      closeEdit(tag.parentNode.parentNode);
   }
}

function savePlayer(tag)
{
   var inputs = tag.parentNode.getElementsByTagName("input");
   var querystring = "";

   for (var i = 0; i < inputs.length; i++)
   {
      querystring += inputs[i].name + "=" + inputs[i].value + "&";
   }
   querystring = querystring.substring(0, querystring.lastIndexOf("&"));

   var editRow = tag.parentNode.parentNode.parentNode;
   toggleEditText(editRow.previousSibling.lastChild.firstChild);
   editRow.parentNode.removeChild(editRow);

   httpObject = getHTTPObject();

	if (httpObject != null)
	{
		httpObject.open("GET", "save.php?" + querystring, true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}
}

function saveTeam(tag)
{
   var sel = document.getElementById("draftedTeam");
   var dataset = document.getElementById("i");
   var id = document.getElementById("mlbamID");
   var querystring = "mlbamID=" + id.value + "&tm=" + sel.selectedIndex + "&i=" + dataset.value;

   var editRow = tag.parentNode.parentNode.parentNode;
   toggleEditText(editRow.previousSibling.lastChild.firstChild);
   editRow.parentNode.removeChild(editRow);

   httpObject = getHTTPObject();

	if (httpObject != null)
	{
		httpObject.open("GET", "save.php?" + querystring, true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}
}

function toggleEditText(tag)
{
   if (tag.innerHTML == "Edit")
   {
      tag.innerHTML = "Close";
      return true;
   }
   else
   {
      tag.innerHTML = "Edit";
      return false;
   }  
}

function getHTTPObject()
{
	if (window.ActiveXObject) 
	{
		return new ActiveXObject("Microsoft.XMLHTTP");
	}
	else if (window.XMLHttpRequest)
	{
		return new XMLHttpRequest();
	}
	else
	{
		return null;
	}

}

function setOutput()
{
	if (httpObject.readyState == 4)
	{
		location.reload();
	}
}

function updateProgress(newText)
{
   document.getElementById("progress").innerHTML = newText;
}

function finishProgress()
{
   document.getElementById("progress").style.display = "none";
}
