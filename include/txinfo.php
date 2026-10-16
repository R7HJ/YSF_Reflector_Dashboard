  <div class="panel panel-default panel-danger">
  <!-- Standard-Panel-Inhalt -->
  <div class="panel-heading">Currently Transmitting</div>
  <!-- Tabelle -->
  <div class="table-responsive">
  <table id="currtx" class="table table-condensed table-striped table-hover">
   <thead>
    <tr>
      <th>Time (<?php echo(TIMEZONE) ?>)</th>
      <th>Callsign</th>
      <th>Target</th>
      <th>Gateway</th>
      <th>TX-Time</th>
    </tr>
   </thead>
   <tbody id="txline">
   </tbody>
  </table>
  </div>
</div>
<script>
function ChangeFavicon(url) {
 var link = document.querySelector("link[rel*='icon']") || document.createElement('link');
 link.type = 'image/x-icon';
 link.rel = 'shortcut icon';
 link.href = url;    //path to your icon
 document.getElementsByTagName('head')[0].appendChild(link);
}

function doXMLHTTPRequest(scriptname, elem) {
	var xmlhttp;
	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	} else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			document.getElementById(elem).innerHTML=xmlhttp.responseText;
		if (xmlhttp.responseText.includes("tr")) {
				ChangeFavicon("favicontx.ico");
                        } else {
				ChangeFavicon("favicon.ico");
                        }

		}
	}
	xmlhttp.open("GET",scriptname+(scriptname.indexOf("?")===-1?"?":"&")+"_t="+new Date().getTime(),true);
	xmlhttp.send();
}

function refreshInQSOAndLastHeardList() {
	doXMLHTTPRequest("txinfo.php","txline");
}

var transmitting = false;
function loadXMLDoc() {
	var xmlhttp;
	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	} else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			document.getElementById("txline").innerHTML=xmlhttp.responseText;
			if (xmlhttp.responseText.includes("tr")) {
				ChangeFavicon("favicontx.ico");
			} else {
				ChangeFavicon("favicon.ico");
			}
		}
	}
	xmlhttp.open("GET","txinfo.php?_t="+new Date().getTime(),true);
	xmlhttp.send();

	var timeout = window.setTimeout("loadXMLDoc()", 1000);
}
loadXMLDoc();
</script>
