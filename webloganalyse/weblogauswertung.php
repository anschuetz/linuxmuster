<?php 
// weblogauswertung.php
// Skript zum einfachen Auswerten der Internetzugriffe aus dem Schulnetz
// getestet mit der Linux-Musterloesung PaedML4.0.2
// Skript geschrieben von Jesko Anschütz Januar 2009
// jesko.anschuetz@montfort-realschule.de
// in diesem Skript braucht nichts veraendert zu werden.
// alles kann auf der Konfigurationsseite eingestellt werden.

// Änderungen seit V0.1.2b:
// - Umlaute in den Kommentaren korrigiert
// - Funktionen eingebaut, die den Zeitpunkt des ersten und letzten LOG-Eintrages ermittelt, damit klar ist, ob die Anzeige aktuell ist.
// - Funktion eingebaut, die (falls gewünscht und erlaubt) prüft, ob ein Update vorliegt.

$skript_version="V0.9.2";
$skript_revision = 147;
$config_datei="wla_weblogauswertung.conf";   // wie heisst die Konfigurationsdatei?


ini_set('display_errors', (isset($_GET['debug']) ? true : false)); // debug parameter in der URL prüfen
if (isset($_GET['debug'])) error_reporting( E_ALL );

function update_da(&$aktuelle_revision)
{
  global $skript_revision;
  global $konfiguration;

	$eingabe = 'wget -o /dev/null -O - http://server.montfort-realschule.de/paedml_skripte/revision'; 
	if ($konfiguration['bei_neuer_version_benachrichtigen']) $aktuelle_revision = trim(shell_exec($eingabe)); // nochmal prüfen, ob "heimtelefonieren" erlaubt
	else $aktuelle_revision=$skript_revision; // wenn nicht erlaubt, dann so tun, als wäre es aktuell...
	return ((int)$aktuelle_revision-(int)$skript_revision);
}

function konfiguration($dateiname, $konfiguration = 'Ich will nur lesen ;)', $suchbegriffe = false, $verbose = false)
{
	
	if ($konfiguration == 'Ich will nur lesen ;)')			// wenn nur gelesen werden soll...
	{
		$konfiguration=parse_ini_file($dateiname); // Konfiguration einlesen
		// jetzt auf alle notwendigen Variablen testen und ggf. default-Werte setzen
		if (!isset($konfiguration['debug'])) $konfiguration['debug']="false";
		if (!isset($konfiguration['ausgabeformat'])) $konfiguration['ausgabeformat']="html";
  		if (!isset($konfiguration['bei_neuer_version_benachrichtigen'])) $konfiguration['bei_neuer_version_benachrichtigen']="false";
		if (!isset($konfiguration['suchbegriffsdatei'])) $konfiguration['suchbegriffsdatei']="suchbegriffe";
		if (!isset($konfiguration['filterausdrucksdatei'])) $konfiguration['filterausdrucksdatei']="filterausdruck";
		if (!isset($konfiguration['logfilepfad']))  $konfiguration['logfilepfad'] ="";
		if (!isset($konfiguration['zu_durchsuchendes_weblog'])) $konfiguration['zu_durchsuchendes_weblog']="access.log";
		if (!isset($konfiguration['urlkuerzen'])) $konfiguration['urlkuerzen']="true";
		if (!isset($konfiguration['nurhauptdomainzeigen'])) $konfiguration['nurhauptdomainzeigen']="false";
		if (!isset($konfiguration['kuerzenauf'])) $konfiguration['kuerzenauf']="100";
		if (!isset($konfiguration['datumsformat'])) $konfiguration['datumsformat']="d.m.Y H:i:s";
		if (!isset($konfiguration['schriftart'])) $konfiguration['schriftart']="Arial";
		if (!isset($konfiguration['schriftgroesse'])) $konfiguration['schriftgroesse']="10";
		if (!isset($konfiguration['farbe_geradezeilen'])) $konfiguration['farbe_geradezeilen']="#dddddd";
		if (!isset($konfiguration['farbe_ungeradezeilen'])) $konfiguration['farbe_ungeradezeilen']="#ffffff";
		if (!isset($konfiguration['farbe_suchbegriff'])) $konfiguration['farbe_suchbegriff']="#FFFF33";
		if (!isset($konfiguration['breite_zeitspalte'])) $konfiguration['breite_zeitspalte']="120";
		if (!isset($konfiguration['breite_ipspalte'])) $konfiguration['breite_ipspalte']="100";
		if (!isset($konfiguration['breite_urlspalte'])) $konfiguration['breite_urlspalte']="780";
		if (!isset($konfiguration['zeitstempel_spalte'])) $konfiguration['zeitstempel_spalte']="1";
		if (!isset($konfiguration['IP_spalte'])) $konfiguration['IP_spalte']="3";
		if (!isset($konfiguration['url_spalte'])) $konfiguration['url_spalte']="7";
		if (!isset($konfiguration['hilfe_texte_anzeigen'])) $konfiguration['hilfe_texte_anzeigen']="true";
		if (!isset($konfiguration['maskteachers'])) $konfiguration['maskteachers']="true";
		if (!isset($konfiguration['userloginsdatei'])) $konfiguration['userloginsdatei']="/var/log/linuxmuster/userlogins";
		if (!isset($konfiguration['zahl_logrotate'])) $konfiguration['zahl_logrotate']="20";
		if (!isset($konfiguration['titel_boesewicht'])) $konfiguration['titel_boesewicht']="Zugriff auf nicht erlaubte Webseiten";
 		if (!isset($konfiguration['anmerkung_boesewicht'])) $konfiguration['anmerkung_boesewicht']=" ";
 		if (!isset($konfiguration['umgekehrtsortieren'])) $konfiguration['umgekehrtsortieren']="true";
		return $konfiguration; 		// Konfiguration zurückgeben
	}
	else	// schreibe die Konfiguration...
	{	if (isset($_POST['suchbegriffsdatei']) AND isset($_POST['suchbegriffe'])) // nur wenn Suchbegriffe
		{									  // übergeben wurden
			if ($verbose) echo "Suchbegriffe in Datei '".$_POST['suchbegriffsdatei']."'schreiben... ";
			$suchfile= fopen($konfiguration['skriptpfad'].
					 $_POST['suchbegriffsdatei'],"w");	// Suchbegriffs-Datei zum schreiben öffnen
			$status = fwrite($suchfile, $_POST['suchbegriffe']);	// Suchbegriffe schreiben...
			fclose($suchfile);					// Datei ordentlich schließen
			if (!$status) die("<br>Fehler beim Beschreiben der Suchbegriffsdatei! <br />
					Hat das Skript schreibenden Zugriff auf '".
					$_POST['suchbegriffsdatei']."' ???");
			else 
			if ($verbose) echo "[ERFOLG]<br/>\n";
		}
		$konfigfile = fopen($konfiguration['skriptpfad'].$dateiname,"w"); 	// Datei zum Schreiben öffnen 
		
		foreach($konfiguration as $tempname=>$tempvalue) 	// alle Formulardaten in die Datei
		{
			if($tempname == 'suchbegriffe' or $tempname =='aktion') continue;
			$status = fwrite($konfigfile,$tempname.' = "'.$tempvalue.'"'."\n");		// sichern.
			if ($verbose) echo "<i>$tempname".' = "'.$tempvalue.'"</i> in Datei '.$dateiname.' schreiben... ';
			if (!$status) die("<br>Fehler beim Sichern der Konfiguration!<br>
				Hat das Skript schreibenden Zugriff auf $dateiname ???"); 
			if ($verbose) echo '[ERFOLG]<br>';
		}
		fclose($konfigfile);		// Datei ordentlich schließen
		return $konfiguration;
	}

}


function allesklar($verbose=false) // Prüft Dateirechte und gibt ggf. Hinweise
{
  if ($verbose) echo "<h1>Pr&uuml;fe die Voraussetzungen</h1><hr />";
  global $konfiguration;
  global $config_datei;
  
  $rw = array( 	$konfiguration['skriptpfad'].$konfiguration['filterausdrucksdatei'],
  		$konfiguration['skriptpfad'].$konfiguration['suchbegriffsdatei'],
		$konfiguration['skriptpfad'].$config_datei
	     );
  $ro = array(  $konfiguration['logfilepfad'].$konfiguration['zu_durchsuchendes_weblog']
  	     );

/*  Folgender Abschnitt war unpraktikabel, weil dadurch das Skript nicht mehr für root ausführbar war.
    if (is_writeable(basename($_SERVER["SCRIPT_FILENAME"])))
  {
    if ($verbose) echo "Datei '".basename($_SERVER["SCRIPT_FILENAME"])."' ist schreibbar! Sie darf nur lesbar sein!";
    else return false;
  }
*/
  foreach ($rw as $dateiname)
  {
    if (!file_exists($dateiname)) 
    {
      if ($verbose) echo "Datei '$dateiname' existiert nicht!<br>";
      else return false;
    }
    elseif (!is_writeable($dateiname)) 
    {
      if ($verbose) echo "Datei '$dateiname' ist nicht beschreibbar!<br>";
      else return false;
    }
  }
  
  foreach ($ro as $dateiname)
  {
    if (!file_exists($dateiname)) 
    {
      if ($verbose) echo "Datei '$dateiname' existiert nicht!<br>";
      else return false;
    }
    elseif (!is_readable($dateiname)) 
    {
      if ($verbose) echo "Datei '$dateiname' ist nicht lesbar!<br>";
      else return false;
    }
  }
  return true; 
}



function erster_logeintrag($logdatei)
{
	global $konfiguration;
	$eingabe = "head -1 $logdatei |cut -d' ' -f 1";
	if ($konfiguration['debug']=="true") echo "<br>\n(".__LINE__.")".__FUNCTION__.": \$eingabe: $eingabe<br>\n";
	$timestamp = trim(str_replace("\n","",shell_exec($eingabe)));
        if ($konfiguration['debug']=="true") echo "<br>\n(".__LINE__.")".__FUNCTION__.": \$timestamp: $timestamp<br>\n";
	return $timestamp;
}
function letzter_logeintrag($logdatei)
{
	global $konfiguration;
	$eingabe = "tail -1 $logdatei |cut -d' ' -f 1";
	if ($konfiguration['debug']=="true") echo "<br>\n(".__LINE__.")".__FUNCTION__.": \$eingabe: $eingabe<br>\n";
	$timestamp = trim(str_replace("\n","",shell_exec($eingabe)));
        if ($konfiguration['debug']=="true") echo "<br>\n(".__LINE__.")".__FUNCTION__.": \$timestamp: $timestamp<br>\n";
	return $timestamp;
}

function loginfinden($arr_log, $zugriff_zeit)

{
	global $konfiguration; // Konfiguration für die Funktion verfügbar machen
	$debug=($konfiguration['debug']=="true");
	$boesewicht = array();
	if ($konfiguration['debug']=="true") {echo "<hr /> (".__LINE__.") vorgefiltertes Log:";
	print_r($arr_log);
	echo "<br>";}
	
	$kleinste_diff = 9999999;  // unmöglichen Startwert setzen
	foreach($arr_log as $eintrag) // alle vorgefilterten Logeinträge durchlaufen
	{
		if ($konfiguration['debug']=="true") echo "<hr/ (".__LINE__.")\$arr_log Logeintrag: $eintrag<br>";
		$login_zeit = substr(trim($eintrag),7,6);
		$diese_diff = $zugriff_zeit - $login_zeit;  // Zeitdifferenz zwischen Zugriff und Login berechnen
		if ($konfiguration['debug']=="true") echo "(".__LINE__.")Diese: $diese_diff, Kleinste: $kleinste_diff<br>";
		if (((int)$diese_diff < (int)$kleinste_diff) && ((int)$diese_diff > 0))  // die kleinste Differenz gewinnt :)
		{
			$boesewicht['login'] = (($konfiguration['maskteachers']=="true" AND (strpos($eintrag,"teacher")!==false)) ? "***LEHRER***" : $eintrag); 
			$kleinste_diff = $diese_diff; // dies aktuelle Differenz ist jetzt die kleinste...
			
		} 
		if (($konfiguration['debug']=="true") && ($login_zeit < $zugriff_zeit))  // ggf debugmeldungen generieren
		   echo "(".__LINE__.")"."$login_zeit kommt in Frage ($eintrag)<br>";
		else if ($debug) echo "(".__LINE__.")"."$login_zeit  kommt nicht in Frage! ($eintrag)<br>";

	}
	$kleinste_diff = 9999999; // Logout finden funktioniert gleich wie Login finden nur andersrum :)
	foreach($arr_log as $eintrag)
	{
		if ($konfiguration['debug']=="true") echo "<hr />(".__LINE__.") \$arr_log Eintrag".$eintrag."<br>";
		$login_zeit = substr(trim($eintrag),7,6);
		$diese_diff = $login_zeit - $zugriff_zeit; // hier ist es andersrum als oben 
		if ($konfiguration['debug']=="true") echo "(".__LINE__.")"."Diese: $diese_diff, Kleinste: $kleinste_diff<br>";
		if (((int)$diese_diff < (int)$kleinste_diff) && ((int)$diese_diff > 0)) 
		{
			$boesewicht['logout'] = (($konfiguration['maskteachers']=="true" AND (strpos($eintrag,"teacher")!==false)) ? "***LEHRER***" : $eintrag); 
			$kleinste_diff = $diese_diff;
		} 
	
	} 
	if ($konfiguration['debug']=="true") {echo "(".__LINE__.") \$boesewicht: "; print_r($boesewicht);}
	if ($boesewicht['login'] == "***LEHRER***") return $boesewicht;  // vielleicht will man die Privatsphäre schützen
	if (strpos($boesewicht['logout'],"logs out")==false) 
 	  $boesewicht['logout']="LOGOUT konnte nicht fehlerfrei ermittelt werden! (evtl Konfiguration 'zahl_logrotate' erh&ouml;hen?)<br /><span style =\"font-size:0.6em\">".$boesewicht['logout']."</span>"; 
	if (strpos($boesewicht['login'],"logs in")==false) 
	  $boesewicht['login']="LOGIN konnte nicht fehlerfrei ermittelt werden! (evtl Konfiguration 'zahl_logrotate' erh&ouml;hen?)<br /><span style =\"font-size:0.6em\">".$boesewicht['login']."</span>";
	return $boesewicht; // wenn ein Sinnfehler in den ermittelten Einträgen vorgefunden wird, wird die Fehlermeldung zurückgegeben
}

function boesewicht_ermitteln($ip, $zeitstempel, $zahl_logrotate = 5)
{
	global $konfiguration; // Konfiguration für die Funktion verfügbar machen...

	$dateiname = $konfiguration['userloginsdatei']; // dateinamen ermitteln
	$datum = date("ymd",$zeitstempel);	// Datum formatieren
	$zeit = date("His",$zeitstempel);	// Zeit formatieren
	if ($konfiguration['debug']=="true") echo "(".__LINE__.")".$datum."|".$zeit."<br>"; //debugmeldungen ausgeben
	$such_regexp = "$datum.*".str_replace(".","\.",$ip).")"; // wir suchen nach einer bestimmten IP und einem bestimmten Datum
	$filter_regexp ="127\.0\.0\.1\|0\.0\.0\.0"; // wir wollen die komischen Einträge (ich nenn sie gerne Bullshit) nicht sehen.
	$eingabe="cat ".$dateiname."| grep -e \"$such_regexp\" | grep -v -e \"$filter_regexp\"";  //  grep verwenden um die  Datei zu filtern
	$ausgabe=preg_replace('/\s\s+/', ' ', nl2br(shell_exec($eingabe))); //  Multispaces raus und NewLines ersetzen 

	if ($konfiguration['debug']=="true") echo "(".__LINE__.")".$eingabe."<br>"; // debugmeldungen ausgeben
	for($i=1;$i<$zahl_logrotate;$i++) // auch die gezippten Dateien durchsuchen
	{
		$zipdateiname = $dateiname.".".$i.".gz"; 
		if (file_exists($zipdateiname))
		{
			$eingabe="zcat ".$zipdateiname." | grep -e \"$such_regexp\" | grep -v -e \"$filter_regexp\"";  //  wie oben, aber zcat
			$ausgabe.=preg_replace('/\s\s+/', ' ', nl2br(shell_exec($eingabe))); //  Multispaces raus und NewLines ersetzen 
		}
	}
	$allezeilen = explode("<br />", $ausgabe);  // die einzelnen Zeilen der	Datei in ein Array schreiben
					 
	return loginfinden($allezeilen,$zeit);  // gefundenen Login zurückgeben
}

function wort_highlight(&$zeichenkette, $wortliste) // Suchbegriffe bunt hinterlegen
{
	global $konfiguration;	//konfiguration innerhalb der Funktion verfügbar machen
	$highlight_style = ' style="background-color:'.$konfiguration['farbe_suchbegriff'].';"';
	foreach($wortliste as $hervorzuheben)	// alle suchbegriffe durchgehen
	{
		$zeichenkette = str_replace($hervorzuheben,"<span $highlight_style>$hervorzuheben</span>",$zeichenkette);
	}
	
}
function ausgabestring_ergebnis() // das Ergebnis in Zeichenkette schreiben 
{
	global $konfiguration;  //konfiguration innerhalb der Funktion verfügbar machen
	$str_ausgabe = $str_tablebody=''; // Variablen löschen
	$br = ($konfiguration['ausgabeformat']=="html" ? "<br />" : "\n") ;  	// abhängig vom Ausgabeformat <br /> 
										// oder \n (newline) als Zeilenumbruch
	$kuerzen = $konfiguration['urlkuerzen'];				// damit der Quelltext weiter unten 
	$kuerzenauf = $konfiguration['kuerzenauf'];				// lesbarer bleibt
	$suchergebnis = reduziere_array(log_nach_array($konfiguration['zu_durchsuchendes_weblog'], 	// Suchergebnis  
					suchbegriffe(),	filterausdruck())); 		// ermitteln 
				
	switch($konfiguration['ausgabeformat'])		// weitere Ausgabe abhängig vom Ausgabeformat
	{
		case "text": 				// wenn Textformat gewünscht...
				foreach ($suchergebnis as $zeile) 
				{
					foreach ($zeile as $ding) // einfachste Ausgabe der Ergebnisse
						if ($kuerzen==true) $str_ausgabe .= substr($ding,0,$kuerzenauf)." "; 
						else $str_ausgabe .= $ding." ";
					$str_ausgabe .= "\n";
				}
				
		break;
		default:
				$filename = basename($_SERVER["SCRIPT_FILENAME"]);  //skriptnamen ermitteln
				$geradezeile=false; // mit irgendwas fangen wir an die Zeilen zu färben
				$style_zeile='style="background-color:'.$konfiguration['farbe_geradezeilen'].';"';
				$style_zeit='style="width:'.$konfiguration['breite_zeitspalte'].'px;"';
				$style_ip='style="width:'.$konfiguration['breite_ipspalte'].'px;"';
				$style_url='style="width: '.$konfiguration['breite_urlspalte'].'px;"';
				foreach ($suchergebnis as $spalte)
				{
				
					if ($geradezeile = !$geradezeile) 
						$style_zeile='style="background-color:'.$konfiguration['farbe_geradezeilen'].';"';
					else 	$style_zeile='style="background-color:'.$konfiguration['farbe_ungeradezeilen'].';"';
					$zeitstempel = $spalte['timestamp'];
					$ip = $spalte['ip'];
					$zahl_logrotate = $konfiguration['zahl_logrotate'];
					$logeintrag = base64_encode(date($konfiguration['datumsformat'],$spalte['timestamp']).": Zugriff von ".$ip." auf ".$spalte['url']);
					$str_tablebody .= "\n<tr $style_zeile>";
					$str_tablebody .= "<td $style_zeit>".date($konfiguration['datumsformat'],$spalte['timestamp']).'</td>'.
							 "<td $style_ip>
							 <a href=\"?ErmittleBoesewicht&zeitstempel=$zeitstempel&ip=$ip&zahl_logrotate=$zahl_logrotate&logeintrag=$logeintrag\">".
							 $spalte['ip'].'</a></td>'.
							 "<td $style_url><a href=\"".$spalte['url']."\" target=\"_blank\">".$spalte['url'].'</a></td>'; //kein Worthighlight moeglich mit dieser Zeile
					// "<td $style_url>".$spalte['url'].'</td>'; //keine anklickbaren links, dafuer geht dann worthighlight
					// entweder das (mit Worhighlight) oder anklickbare links

					$str_tablebody .='</tr>';
				}
				$str_tablekopf = "\n".'<table style="table-layout:fixed; font-size:'.
						  $konfiguration['schriftgroesse'].'px;">'.
						  "\n<tr><td $style_zeit>Datum/Zeit:</td><td $style_ip>Zugriff von:</td><td $style_url>Zugriff auf:</td>";
				$str_tablefuss = '</table>';
				//entweder worthighlight oder anklickbare Links...
				//wort_highlight($str_tablebody,suchbegriffe());
				$str_ausgabe = $str_tablekopf.$str_tablebody.$str_tablefuss;
					
		break;
	}
	return $str_ausgabe;
}

function filterausdruck($filterausdruck = "Ich will nur lesen ;)")  // Filterausdruck ist das, was NICHT angezeigt werden soll
{ // wenn die Funktion ohne Filterausdruck aufgerufen wird, dann liest sie den Ausdruck aus der Datei. Andernfalls schreibt sie den Ausdruck IN die Datei.
	global $konfiguration;
	$dateiname = $konfiguration['skriptpfad'].$konfiguration['filterausdrucksdatei'];
	if ($filterausdruck == "Ich will nur lesen ;)")			// Filterausdruck lesen, ohne die Datei zu verändern...
	{
			$filterdatei = fopen($dateiname, "r");		// o.angeg. Datei öffnen und
			if (!$filterdatei) die("Skript in Funktion ".__FUNCTION__." abgebrochen!<br />Datei '$dateiname' konnte nicht zum Lesen geöffnet werden!<br />Ist die Datei im Skriptverzeichnis vorhanden?");
			//filterausdruck aus Datei lesen und alle | durch \| ersetzen. dann alle doppelten \\ korrigieren.
			$filterausdruck = str_replace('\\\\','\\', str_replace('|', '\|', str_replace("\n","",fgets($filterdatei))));
			fclose($filterdatei);	// ordentlich schließen...
			return $filterausdruck;
	}
	else	// sonst Filterausdruck in die Datei schreiben...
	{
			$filterausdruck = (trim($filterausdruck) == '' ? $filterausdruck = "Aktuell wird nichts ausgeblendet! Um etwas auszublenden, hier einen RegExp eingeben! " : $filterausdruck);
			$filterdatei = fopen($dateiname,"w"); 	// Datei zum Schreiben öffnen
			$status = fwrite($filterdatei, $filterausdruck);  // Ausdruck sichern
			fclose($filterdatei);				// Datei ordentlich schließen
			if (!$status) die($dateiname." konnte nicht geschrieben werden. ". // Wenn ein Fehler aufgetreten ist, Skript abbrechen
				"Skript in Funktion '".__FUNCTION__."' beendet<br />Datei '$dateiname' muss Dateirecht 666 (rw-rw-rw) haben!");
			return $filterausdruck;	 // Filterausdruck zurückgeben									
	}
}

function suchbegriffe($suchbegriffe = Array("Ich will nur lesen ;)"))
{
	global $konfiguration;
	$dateiname = $konfiguration['skriptpfad'].$konfiguration['suchbegriffsdatei'];
	if ($suchbegriffe[0] == "Ich will nur lesen ;)")
	{ // wenn die Funktion ohne Suchbegriffe (als Array) aufgerufen wird, dann liest sie die Suchbegriffe aus der Datei. Andernfalls schreibt sie die Begriffe IN die Datei.
		$suchbegriffsdatei = fopen($dateiname,"r"); 		// Datei zum Lesen öffnen
		if (!$suchbegriffsdatei) die("Skript in Funktion suchbegriffe() abgebrochen!<br />$dateiname konnte nicht zum Lesen geöffnet werden!<br />Ist die Datei im Skriptverzeichnis vorhanden?");
		while (!feof($suchbegriffsdatei)) 	// Datei Zeilenweise in ein Array schreiben
		{					
			$zeile = fgets($suchbegriffsdatei);			// Zeile einlesen
			$zeile = preg_replace("/[^a-zA-Z0-9_.-]/","",$zeile);	// Sonderzeichen aus dem Suchbegriff entfernen 
			if ($zeile) $zeilenarray[].= $zeile;			// Wenn Zeile nicht leer ist, dem Array hinzufügen  
			if ($konfiguration['debug']=="true") {echo '('.__LINE__.') in Funktion '.__FUNCTION__.'$zeile: '.$zeile.'<BR>';} //debugmeldungen falls gewünscht. 
		}
		fclose ($suchbegriffsdatei); 					// Datei ordentlich schließen
		if ($konfiguration['debug']=="true") {echo '('.__LINE__.') in Funktion '.__FUNCTION__.'$zeilenarray: '; print_r($zeilenarray);} //debugmeldungen falls gewünscht. 
		return $zeilenarray; 						// Suchbegriffe zurückgeben
	}
	else
	{
		$suchbegriffsdatei = fopen($dateiname,"w"); 			// Datei zum Schriben öffnen
		if (!$suchbegriffsdatei) die("Skript in Funktion ".__FUNCTION__." abgebrochen!<br />$dateiname konnte nicht zum Schreiben geöffnet werden!<br />Ist die Datei im Skriptverzeichnis vorhanden und hat die Dateirechte rw-rw-rw (666)?");
		foreach ($suchbegriffe as $zeile)
		{
			$zeile = preg_replace("/[^a-zA-Z0-9_.-]/","",$zeile);	// Sonderzeichen aus dem Suchbegriff entfernen
			if ($zeile) fwrite($suchbegriffsdatei, $zeile."\n");	// Nichtleere Zeilen in die Datei schreiben
		}
		fclose ($suchbegriffsdatei); 					// Datei ordentlich schließen
		return suchbegriffe($dateiname);				// Suchbegriffe aus Datei lesen und zurückgeben
	}
}
	
function log_nach_array($dateiname, $suchbegriffe, $filterausdruck) // WebLogdatei auslesen und vorgefiltert in Array schreiben 
{
	global $konfiguration;
	$such_regexp 	= "";	// beide Variablen leer lassen...
	$filter_regexp 	= $filterausdruck; 
	foreach ($suchbegriffe as $suchwort) // mit allen Suchbegriffen einen regulären Ausdruck erstellen für grep...
	{
		if (!$such_regexp) $such_regexp = $suchwort; else $such_regexp .= '\|'.$suchwort;
	}
	if ($konfiguration['umgekehrtsortieren']=="true")
	 $cat_befehl = "tac "; 
	else 
	 $cat_befehl = "cat ";
	$eingabe=$cat_befehl.$dateiname."| grep -e \"$such_regexp\" | grep -v -e \"$filter_regexp\"";  //  grep als Filter verwenden 
	if ($konfiguration['debug']=="true") echo $konfiguration['debug']."(".__LINE__.$eingabe."<br>"; //debugmeldungen ausgeben
	$ausgabe=preg_replace('/\s\s+/', ' ', nl2br(shell_exec($eingabe))); // Unnütze Spaces löschen und Zeilenumbrüche in <br> umwandeln.
	$allezeilen = explode("<br />", $ausgabe);  // die einzelnen Zeilen der Datei in ein Array schreiben
	array_pop($allezeilen);	// die letzte Zeile ist leer. Deshalb werfen wir sie weg.
	return $allezeilen; // fertiges Array zurückgeben
}

function reduziere_array($log_array) // Das Weblog auf Zeitstempel, IP und URL reduzieren.
{
	global $konfiguration;
	$zeilennummer = 0; // Startwert für den Arrayindex
	foreach ($log_array as $zeile)
	{
		$zeilennummer += 1;	// Arrayindex um eins erhöhen
		$zeile=trim($zeile); 	//Leerzeichen am Anfang und Ende stören ja nur...
		$spalte=explode(" ",$zeile); // Leerzeichen als Spaltentrenner nehmen und in Spalten in Array abspeichern
		$timestamp = $spalte[$konfiguration['zeitstempel_spalte']-1]; 	//zeit in Variable kopieren Spalten werden ab Null gezählt
		$ipaddress = $spalte[(int)$konfiguration['IP_spalte']-1]; 	// IP in Variable kopieren Spalten werden ab Null gezählt
		$url = $spalte[(int)$konfiguration['url_spalte']-1]; 		// url in Variable kopieren Spalten werden ab Null gezählt
		$reduziertes_array[$zeilennummer]['timestamp']=$timestamp;
		$reduziertes_array[$zeilennummer]['ip']=$ipaddress;
		$reduziertes_array[$zeilennummer]['url']=$url;
		if ($konfiguration['nurhauptdomainzeigen']=="true") if ($url[strlen($url)-1]!='/') {unset($reduziertes_array[$zeilennummer]); $zeilennummer -=1; }
	}
	return $reduziertes_array;  // und zurückgeschickt...
}
function kopf($verbose=true) // HTML-Kopf abhängig vom Ausgabeformat
{
	global $konfiguration;
	global $skript_revision;
	global $skript_version;
	if ($konfiguration['ausgabeformat'] == "html")
	{ 
		$style_body = 'style="font-family:'.$konfiguration['schriftart'].'; font-size: 11px;"';
		echo "<html>\n <head>\n <title>Weblogauswertung von Jesko Ansch&uuml;tz - Dateiversion:$skript_version</title>\n</head>\n<body $style_body>";
		if ($verbose) echo "\n<h2>Weblogauswertung</h2>\n";
		if ($verbose) echo "\nDie Log-Datei <i>".$konfiguration['zu_durchsuchendes_weblog']."</i> ber&uuml;cksichtigt Zugriffe vom <b>".
			date('d.m.Y - H:i:s', erster_logeintrag($konfiguration['zu_durchsuchendes_weblog']))."</b> bis zum <b>".
			date('d.m.Y - H:i:s', letzter_logeintrag($konfiguration['zu_durchsuchendes_weblog']))."</b>\n";

		if ($konfiguration['debug']=="true") echo update_da($ver).":'$ver'<br />";
		if ($konfiguration['debug']=="true") echo "<br />bei_neuer_version_benachrichtigen: ".$konfiguration['bei_neuer_version_benachrichtigen']."<br />";
		if ($verbose && $konfiguration['bei_neuer_version_benachrichtigen']=="true")
		{
		  if (update_da($ver)) echo '<br /><span style="color:red; font-size:0.7777777em;">Es liegt eine neue Version des Skriptes vor!
						<b>Sie haben Revision '.$skript_revision.', aktuell ist Revision '.$ver.'</b></span><br />';
		}
	}
	else 
	{
		echo "\n";
		echo "-----------------------------------------\n";
		echo "-- Weblogauswertung von Jesko Anschütz --\n";
		echo "-- Dateiversion: $skript_version ----------------\n";
		echo "-----------------------------------------\n";
		echo "ZEITSTEMPEL: ".date('d.m.Y h:i')."\n";
		echo "-----------------------------------------\n";
  		if ($konfiguration['bei_neuer_version_benachrichtigen']=="true")
		{
		  if (update_da($ver)) echo "Es liegt eine neue Version des Skriptes vor!\n Sie haben $skript_version, aktuell ist $ver\n";
		}
	}
}
function fuss() // 
{
	global $konfiguration;
	if ($konfiguration['ausgabeformat'] == "html")
	{ 
		echo "\n</body>\n</html>";
	}
	else 
	{
		echo "\n";
		echo "-----------------------------------------\n";
		echo "um im Browser auf die Konfigurationsseite zu gelangen,\n http://pfad/zum/skript/weblogauswertung.php?konfiguration=1\n";
		echo "--------- Ende der Ausgabe! -------------\n";
		echo "-----------------------------------------\n";
	}
}

function skriptpfad_ermitteln() // um ihn automatisch in das Konfig-File zu schreiben... warum? weiss ich nicht mehr...
{

		$pathfilename = $_SERVER["SCRIPT_FILENAME"];
		$filename = basename($_SERVER["SCRIPT_FILENAME"]);
		$pfad = substr($pathfilename,0,strlen($pathfilename)-strlen($filename ));
		return $pfad;
}

// Beginn des Hauptprogrammes
// zuerst schauen, ob das Skript durch Klick auf eine IP-Adresse aufgerufen wurde
// In Abhängigkeit davon $str_aktion zuweisen...
$str_aktion = (isset($_GET['ErmittleBoesewicht'])) ? 'ErmittleBoesewicht' : ((isset($_POST['aktion'])) ? trim($_POST['aktion']) : '');

switch($str_aktion)
{
    case "ErmittleBoesewicht":
    	$konfiguration = konfiguration($config_datei);	// Konfiguration einlesen
	$ip = (isset($_GET['ip'])) ? $_GET['ip'] : false; // ip aus der URL auslesen
	$zeitstempel = (isset($_GET['zeitstempel'])) ? $_GET['zeitstempel'] : false; // zeitstempel aus URL auslesen
	$zahl_logrotate = (isset($_GET['zahl_logrotate'])) ? $_GET['zahl_logrotate'] : $konfiguration['zahl_logrotate']; // ...
	$logeintrag = (isset($_GET['logeintrag'])) ? $_GET['logeintrag'] : false; // Log-Eintrag aus der URL entschlüsseln  
	if ($logeintrag) $logeintrag = base64_decode($logeintrag);
	$boesewicht = boesewicht_ermitteln($ip, $zeitstempel, $zahl_logrotate); // wer ist der Bösewicht?
	kopf(false);	// kopf ausgeben
	echo "<h2>".$konfiguration['titel_boesewicht']."</h2>";
	if ($konfiguration['debug']=="true") echo "<span style=\"font-size: 0.7em\">Suche in den letzten ".$konfiguration['zahl_logrotate']." userlogins.X.gz</span>".'<span style="color: #880000;">'; 
	echo "<hr />".$boesewicht['login']."<br />";
	if ($logeintrag) echo '<p style="font-size:0.8em; font-weight:bold; font-style:italic;">'.$logeintrag."</p>";
	echo $boesewicht['logout']."</span><hr />";
	if ($konfiguration['hilfe_texte_anzeigen']=="true") echo "Achtung! Trotz sorgf&auml;ltiger Programmierung des Skriptes kann es immer sein, 
		dass bei der Ermittlung des 'B&ouml;sewichts' ein Fehler unterl&auml;uft!<br />
		Bitte vor allzuharten Strafen immer das Ergebnis in den Logdateien &uuml;berpr&uuml;fen!<br />";
	echo $konfiguration['anmerkung_boesewicht']."<br />";
	echo '<a href="'.basename($_SERVER["SCRIPT_FILENAME"]).'">[ZUR&Uuml;CK]</a>';
	break;

    case "konfigurieren":
	$konfiguration = konfiguration($config_datei);	// Konfiguration einlesen
	$suchbegriffe = implode(suchbegriffe(),"\n");
	kopf($konfiguration['ausgabeformat'],$skript_version);
	// formular mit allen Konfigurationsvariablen aufbauen...
	$button_konfiguration_sichern = "<input type=\"submit\" name=\"aktion\" value=\"Konfiguration sichern\" style=\"font-size: ".
					$konfiguration['schriftgroesse']." px;\">";
	echo "
	<form name=\"Konfigurationsformular\" method=\"post\" action=\"\">\n
	<table>\n
	<tr><td colspan=\"2\">$button_konfiguration_sichern</td></tr>\n";

	foreach($konfiguration as $tempname=>$tempvalue) 
	{
		if ($tempname!='suchbegriffe' AND $tempname !='aktion') 
		  echo "<tr><td>$tempname:</td><td><input type=\"text\" name=\"$tempname\" value=\"$tempvalue\"></td></tr>\n";
	}
	echo "<tr><td colspan=\"2\">Suchbegriffe (<b>GENAU einer</b> pro Zeile, keine Sonderzeichen!</tr>\n
	      <tr><td colspan=\"2\">";
	echo '<textarea name="suchbegriffe" cols="80" rows="30">'.$suchbegriffe.'</textarea></tr>';
	echo "<tr><td colspan=\"2\">$button_konfiguration_sichern</td></tr>\n
		</table>\n</form>\n";
	// Formular zuende.
   break;

   case "Konfiguration sichern":
	
	unset($_POST['Konfiguration sichern']); // unnötige POST-Variablen löschen, damit sie nicht im Konfigurationsfile landen
	$konfiguration = konfiguration($config_datei, $_POST, true, true);		// Konfiguration auf Platte speichern.	
	kopf($konfiguration['ausgabeformat'],$skript_version);
	echo "<hr />\n<form name=\"zurueck\" method=\"post\" action=\"\">\n
	<input type=\"submit\" name=\"submit\" value=\"Zur&uuml;ck zur Logfile-Analyse\" style=\"font-size: ".
	$konfiguration['schriftgroesse']." px;\">\n</form>\n";						
break;
default:
	$konfiguration = konfiguration($config_datei);		// Konfiguration einlesen
	$konfiguration['skriptpfad']=skriptpfad_ermitteln();	
	$konfiguration = konfiguration($config_datei, $konfiguration);	// Konfig speichern, fehlende Einträge ergänzen!
	if (!allesklar()) { allesklar(true); die('<hr /><h3>Bitte Voraussetzungen pr&uuml;fen!</h3>');} //Voraussetzungen prüfen
	if ($konfiguration['debug']=="true")   // debugeinstellungen setzen
	{ 
	  ini_set('display_errors', true);
	  error_reporting(E_ALL);
	  print_r($konfiguration);
	} else ini_set('display_errors', false);
	
	if (isset($_POST['filterausdruck'])) filterausdruck($_POST['filterausdruck']); // ggf.filterausdruck gefunden wurde, in datei schreiben
	$filterausdruck = filterausdruck(); // filterausdruck lesen
	$konfigurationsbutton ="\n".'<form name="Konfigurationsbutton" method="post" action="">
					<input type="submit" name="aktion" value="konfigurieren" 
					style="font-size:'.$konfiguration['schriftgroesse'].'px;">
					</form>'."\n";
	$neu_filtern_button =  "\n".'<form name="Neu-Filtern-Button" method="post" action="">
					<input name="filterausdruck" type="text" id="filterausdruck" size="'.
					min(150,strlen($filterausdruck)).'" '.'value="'.str_replace("\\","",$filterausdruck).
					'" style="font-family: '.$konfiguration['schriftart'].', Verdana, sans-serif;">
					<input type="submit" name="aktion" value="neu filtern" style="font-size:'.$konfiguration['schriftgroesse'].'px;">
					</form>'."\n";

	kopf();
	if ((isset($_GET['konfiguration']) ? true : false) OR ($konfiguration['ausgabeformat'] == "html")) // für den Notfall, wenn man 
	echo "$konfigurationsbutton<hr />$neu_filtern_button";	// sich ausgesperrt hat, ist das die Hintertür zur Konfig

	echo ausgabestring_ergebnis(); // 

	if ((isset($_GET['konfiguration']) ? true : false) OR ($konfiguration['ausgabeformat'] == "html")) echo $konfigurationsbutton;
	break;
}


fuss(); // Seite ordentlich abschließen :)





