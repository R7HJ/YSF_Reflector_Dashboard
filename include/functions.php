<?php
function getYSFReflectorVersion() {
	// returns creation-time of YSFReflector as version-number
	$filename = YSFREFLECTORPATH."/YSFReflector";
	$output = array();
	exec($filename." -v 2>&1", $output);
	if (!isset($output[0]) || !startsWith(substr($output[0],21,8),"20")) {
		return getYSFReflectorFileVersion();
	} else {
		return substr($output[0],21,8)." (compiled ".getYSFReflectorFileVersion().")";
	}
}


function getYSFReflectorFileVersion() {
	// returns creation-time of YSFReflector as version-number
	$filename = YSFREFLECTORPATH."/YSFReflector";
	if (file_exists($filename)) {
		return date("d M Y", filectime($filename));
	}
}

function getGitVersion(){
	if (file_exists(".git")) {
		$output = array();
		exec("git rev-parse --short HEAD", $output);
		if (!isset($output[0])) {
			return 'GitID unknown';
		}
		return 'GitID #<a href="https://github.com/dg9vh/YSFReflector-Dashboard/commit/'.$output[0].'" target="_blank">'.$output[0].'</a>';
	} else {
		return 'GitID unknown';
	}
}

function getYSFReflectorConfig() {
	// loads YSFReflector.ini into array for further use
	$conf = array();
	if ($configs = fopen(YSFREFLECTORINIPATH."/".YSFREFLECTORINIFILENAME, 'r')) {
		while ($config = fgets($configs)) {
			array_push($conf, trim ( $config, " \t\n\r\0\x0B"));
		}
		fclose($configs);
	}
	return $conf;
}

function getConfigItem($section, $key, $configs) {
	// retrieves the corresponding config-entry within a [section]
	$sectionpos = array_search("[" . $section . "]", $configs);
	if ($sectionpos === false) {
		return null;
	}
	$sectionpos++;
	$len = count($configs);
	while ($sectionpos < $len && startsWith($configs[$sectionpos], $key . "=") === false) {
		if (startsWith($configs[$sectionpos], "[")) {
			return null;
		}
		$sectionpos++;
	}
	if ($sectionpos >= $len) {
		return null;
	}
	return substr($configs[$sectionpos], strlen($key) + 1);
}

function getYSFReflectorLogPath() {
	// Returns the logfile to read. Supports both rotated
	// (PREFIX-YYYY-MM-DD.log, FileRotate=1) and non-rotated
	// (PREFIX.log, FileRotate=0) logfiles.
	clearstatcache();
	$dir = YSFREFLECTORLOGPATH;
	$prefix = YSFREFLECTORLOGPREFIX;
	$candidates = array();

	$found = @glob($dir."/".$prefix."*.log");
	if (is_array($found)) {
		$candidates = $found;
	}
	foreach (array($dir."/".$prefix.".log", $dir."/".$prefix."-".date("Y-m-d").".log") as $extra) {
		if (!in_array($extra, $candidates) && file_exists($extra)) {
			$candidates[] = $extra;
		}
	}

	if (count($candidates) === 0) {
		return $dir."/".$prefix."-".date("Y-m-d").".log";
	}

	$newest = $candidates[0];
	$newestTime = -1;
	foreach ($candidates as $candidate) {
		$mtime = @filemtime($candidate);
		if ($mtime !== false && $mtime > $newestTime) {
			$newestTime = $mtime;
			$newest = $candidate;
		}
	}
	return $newest;
}

function getYSFReflectorLog() {
	// Open Logfile and copy loglines into LogLines-Array()
	$logLines = array();
	$logPath = getYSFReflectorLogPath();
	if ($log = fopen($logPath, 'r')) {
		while ($logLine = fgets($log)) {
			if (startsWith($logLine, "M:"))
				array_push($logLines, $logLine);
		}
		fclose($log);
	}
	return $logLines;
}


function listdir_by_date($path){
        $dir = opendir($path);
        $list = array();
        while($file = readdir($dir)){
            if ($file != '.' and $file != '..'){
                $ctime = filectime($path . "/" . $file) . ',' . $file;
                $list[$ctime] = $file;
            }
        }
        closedir($dir);
        krsort($list);
        return $list;
}


function getOldYSFReflectorLog() {
        $dir = YSFREFLECTORLOGPATH;
        $scanlogs = SHOWOLDMHEARD;
        $oldlogLines = array();

        $dir_files = listdir_by_date($dir);
        
        foreach ($dir_files as $file) {
            if ( $file != "." && $file != ".." ) {
                if (strpos($file, YSFREFLECTORLOGPREFIX) !== 0) {
                    continue;
                }
                if ( $scanlogs >= 0 ) {
        
                	if ($log = fopen(YSFREFLECTORLOGPATH."/".$file, 'r')) {
                		while ($oldlogLine = fgets($log)) {
    
                                    if (strpos($oldlogLine, 'Received data from') !== false) {
                			 	array_push($oldlogLines, $oldlogLine);
                                    }
    
                		}
                        fclose($log);
                	}
                $scanlogs = $scanlogs - 1;
                }
            }
        }
	return $oldlogLines;
}


function getShortYSFReflectorLog() {
	// Returns the last "Received"/"watchdog" line of the log (pure PHP,
	// no shell / egrep dependency). Only the tail of the file is read.
	$logPath = getYSFReflectorLogPath();
	$logLines = array();
	if (!is_readable($logPath)) {
		return $logLines;
	}
	$fp = fopen($logPath, 'r');
	if ($fp === false) {
		return $logLines;
	}
	$size = filesize($logPath);
	$chunkSize = 16384;
	if ($size > $chunkSize) {
		fseek($fp, -$chunkSize, SEEK_END);
		$data = fread($fp, $chunkSize);
		$nl = strpos($data, "\n");
		if ($nl !== false) {
			$data = substr($data, $nl + 1);
		}
	} else {
		$data = ($size > 0) ? fread($fp, $size) : "";
	}
	fclose($fp);

	$lines = explode("\n", $data);
	for ($i = count($lines) - 1; $i >= 0; $i--) {
		if (strpos($lines[$i], "Received") !== false || strpos($lines[$i], "watchdog") !== false) {
			$logLines[] = $lines[$i];
			break;
		}
	}
	return $logLines;
}

function getConnectedGateways($logLines) {
	$gateways = Array();
	foreach ($logLines as $logLine) {
		if(strpos($logLine,"YSFReflector") !== false){
			$gateways = Array();
		}
		if(strpos($logLine,"Adding") !== false) {
			$lineParts = explode(" ", $logLine);
			if (!in_array($lineParts[4], array_column($gateways, 'callsign'))) {
				array_push($gateways, Array('callsign'=>$lineParts[4],'timestamp'=>$lineParts[1]." ".substr($lineParts[2],0,8)));
			}
		}
		if(strpos($logLine,"Removing") !== false) {
			$lineParts = explode(" ", $logLine);
			$pos = array_search($lineParts[4],array_column($gateways, 'callsign'));
			if ($pos !== false) {
				array_splice($gateways, $pos, 1);
			}
		}
	}
	return $gateways;
}

function getLinkedGateways($logLines) {
//0000000000111111111122222222223333333333444444444455555555556666666666	
//0123456789012345678901234567890123456789012345678901234567890123456789
//M: 2016-06-24 11:11:41.787 Currently linked repeaters/gateways:
//M: 2016-06-24 11:11:41.787     GATEWAY   : 217.82.212.214:42000 2/60
//M: 2016-06-24 11:11:41.787     DM0GER    : 217.251.59.165:42000 5/60

	$gateways = Array();
	for ($i = count($logLines) - 1; $i > 0; $i--) {
		$logLine = $logLines[$i];
		
		if (strpos($logLine, "Starting YSFReflector") !== false) {
			return $gateways;
		}
		if (strpos($logLine, "No repeaters/gateways linked") !== false) {
			return $gateways;
		}
		if (strpos($logLine, "Currently linked repeaters/gateways") !== false) {
			for ($j = $i+1; $j <= count($logLines); $j++) {
				if (!isset($logLines[$j])) {
					return $gateways;
				}
				$logLine = $logLines[$j];
				if (!startsWith(substr($logLine,27), "   ")) {
					return $gateways;
				} else {
					$timestamp = substr($logLine, 3, 19);
					$callsign = substr($logLine, 31, 10);
					//$ipport = substr($logLine,43);
					$ipport = substr($logLine,31);
					$key = searchForKey("ipport",$ipport, $gateways);
					if ($key === NULL) {
						array_push($gateways, Array('callsign'=>$callsign,'timestamp'=>$timestamp,'ipport'=>$ipport));
					}
				}	
			}
		}
	}
	return $gateways;
}

function getHeardList($logLines) {
	$heardList = array();
	$dttxend = "";
	foreach ($logLines as $logLine) {
		$duration = "transmitting";
		$timestamp = substr($logLine, 3, 19);
		$dttimestamp = new DateTime($timestamp);
		if ($dttxend !== "") {
			$duration = $dttimestamp->diff($dttxend)->format("%s");
		}
		$callsign2 = substr($logLine, strpos($logLine,"from") + 5, strpos($logLine,"to") - strpos($logLine,"from") - 6);
		$callsign = trim($callsign2);
		$target = substr($logLine, strpos($logLine, "to") + 3, strpos($logLine,"at") - strpos($logLine,"to") +6 ); 
		$gateway = substr($logLine, strrpos($logLine,"at") + 3);
		// Callsign or ID should be less than 11 chars long, otherwise it could be errorneous
		if ( strlen($callsign) < 11 ) {
			array_push($heardList, array(convertTimezone($timestamp), $callsign, $target, $gateway, $duration));
		}
		if(strpos($logLine,"end of") !== false || strpos($logLine,"watchdog has expired") !== false || strpos($logLine,"ended RF data") !== false || strpos($logLine,"ended network") !== false) {
			$txend = substr($logLine, 3, 19);
			$dttxend = new DateTime($txend);
		}
	}
	return $heardList;
}

function getLastHeard($logLines) {
	//returns last heard list from log
	$lastHeard = array();
	$heardCalls = array();
	$heardList = getHeardList($logLines);
	$counter = 0;
	foreach ($heardList as $listElem) {
		if(!(array_search($listElem[1], $heardCalls) > -1)) {
			array_push($heardCalls, $listElem[1]);
			array_push($lastHeard, $listElem);
			$counter++;
		}
	}
	return $lastHeard;
}

function getSize($filesize, $precision = 2) {
	$units = array('', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');
	foreach ($units as $idUnit => $unit) {
		if ($filesize > 1024)
			$filesize /= 1024;
		else
			break;
	}
	return round($filesize, $precision).' '.$units[$idUnit].'B';
}
?>
