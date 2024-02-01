<?php
define("IN_MYBB", 1);
define('THIS SCRIPT', 'abschluss.php');

require_once "./global.php";




// Format Entries
require_once MYBB_ROOT . "inc/class_parser.php";
$parser = new postParser;
$parser_options = array(
	"allow_html" => 1,
	"allow_mycode" => 1,
	"allow_smilies" => 1,
	"allow_imgcode" => 1
);


//DATENBANKABFRAGE 
$sql_schulen = "SELECT * FROM " . TABLE_PREFIX . "abschluss_schule";
$query_schulen = $db->query($sql_schulen);

$schulname = $mybb->input['schulname'];
//HAUPTSEITE MIT DER BERECHNUNG
if (!$mybb->input['action']) {
	
	//Wir laden hier die Languages Datei 
	$lang->load('abschluss');

	global $db, $mybb, $lang;
	
	//NAVIGATION BAUEN 
	add_breadcrumb("{$lang->navi} ", "abschluss.php");

	//Option für Schule einfügen
	//DATENBANKABFRAGE 
	$school_query = $db->query("SELECT *
	FROM " . TABLE_PREFIX . "abschluss_schule
	ORDER BY schulname ASC
	");

	while ($row = $db->fetch_array($school_query)) {
		// habe hier noch eingefügt, dass er im Formular oben den Wert speichert, nachdem abgeschickt wurde :)
		if ($row['schulid'] == $mybb->get_input('schule')) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		$select_school .= "<option value='{$row['schulid']}' {$selected}>{$row['schulname']}</option>";
	}

	// Es gibt für HTML-Formulare einen Feld-Typen "date": https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date
	// der speichert das eingegebene Datum dann im Format Jahr-Monat-Datum, das kann man dann via explode() splitten (siehe unten)
	// dann muss man für Tage und Monate kein Dropdown erstellen und hat alles ordentlich in einem Feld im Formular, ist für den User auch schöner


	$birthdate = $mybb->get_input('birthdate');
	$dates = explode("-", $birthdate);
	// $dates ist jetzt eine Liste mit drei Werten: dem Jahr (Key 0), Monat (Key 1) und tag (Key 2)
	$jahrberechnung = $dates[0];
	$monatberechnung = $dates[1];
	$tagberechnung = $dates[2];
	if ($jahrberechnung != '') {

		$lang->load('abschluss');
		
		$schulangabe = $mybb->get_input('schule');

		//Die ID wird in einen Schulnamen für die Ausgabe gewandelt
		$id = $mybb->get_input('schule');

		$schulangabe = $db->simple_select("abschluss_schule", "*", "schulid = {$id}");

		// Hier brauchst du keine while-Schleife, weil du nur ein Ergebnis bekommst mit deiner SQL-Abfrage
		$schulabschluss = $db->fetch_array($schulangabe);
		$schulnamen = $schulabschluss['schulname'];
		$einschulalter = $schulabschluss['schulalter'];
		$schuljahre = $schulabschluss['schuljahre'];
		$monatdanach = $schulabschluss['schulmonate'];
		$gleichesjahr = $schulabschluss['gleichesjahr'];

		// EINSCHULUNG BERECHNEN
		if ($monatberechnung >= $monatdanach) {
			$einschulung = $jahrberechnung + $einschulalter + 1;
		} else {
			$einschulung = $jahrberechnung + $einschulalter;
		}

		// Abschluss
		if ($monatberechnung >= $monatdanach) {
			$abschluss = $einschulung + $schuljahre;
		}
		if ($gleichesjahr == 1 && (int) $monatberechnung >= $monatdanach) {
			$abschluss = $einschulung + $schuljahre - 1;
		} else {
			$abschluss = $einschulung + $schuljahre;
		}

		$nameabschluss_result = $db->query("
			SELECT * FROM " . TABLE_PREFIX . "jahrgaenge 
			LEFT JOIN " . TABLE_PREFIX . "users
			ON " . TABLE_PREFIX . "users.uid = " . TABLE_PREFIX . "jahrgaenge.userid
			WHERE jahrgang = {$abschluss} 
			AND schule = '{$schulnamen}'
		");
		
		$schulpersonen = ""; // Initialisiere die Variable außerhalb der Schleife
		
		while ($user = $db->fetch_array($nameabschluss_result)) {
			$user['username'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);

			// Überprüfe, ob ein Haus eingetragen ist, bevor du die Klammer hinzufügst
        	$hausKlammer = ($user['haus'] != '') ? " (" . $user['haus'] . ")" : "";
        	$schulpersonen .= build_profile_link($user['username'], $user['uid']) . "<i>" . $hausKlammer . "</i> <br>";
    }
	
		if ($schulpersonen == '') {
			$schulpersonen = $lang->noperson;
		}

			//laden des Templates
	eval("\$berechnet .= \"" . $templates->get("abschluss_berechnet") . "\";"); //Template für die Einzelausgabe


	} else {
	
		$berechnet = $lang->abschluss_error;
	}

	
	eval("\$page = \"" . $templates->get("abschluss_main") . "\";");
	output_page($page);
}


//Die Unterseite für die Schulen erstellen
if ($mybb->input['action'] == "schools") {
	
	global $db, $mybb, $templates, $page, $filter_kontinent, $lang;

	//NAVIGATION BAUEN 
	add_breadcrumb("{$lang->navi}", "abschluss.php");
	add_breadcrumb("{$lang->navi_school}");

	//FÜR DEN KONTINENT FILTER
	$filter_kontinent = $db->escape_string($mybb->get_input('filter_kontinent'));
        if(empty($filter_kontinent)) {
            $filter_kontinent = "%";
        }
	
	//DATENBANKABFRAGE 
	$sql_schule = "SELECT * FROM " . TABLE_PREFIX . "abschluss_schule WHERE kontinent LIKE '$filter_kontinent' ORDER BY kontinent ASC";
	$query_schule = $db->query($sql_schule);

	while ($schule = $db->fetch_array($query_schule)) {

		// LEER LAUFEN LASSEN, SONST STEHEN INHALTE VON VORHER DRINNEN VOR ALLEM BEI PHP 8 BEACHTEN!!!
		$schulname = "";
		$schulalter = "";
		$schuljahre = "";
		$schulmonate = "";
		$schulstandort = "";
		$schulgebiet = "";

		//MIT INFOS FÜLLEN 
		$schulname = "<h1>" . $schule['schulname'] . "</h1>"; //Benennung des Titels
		$schuldesc = $parser->parse_message($schule['schuldesc'], $parser_options);
		$schulalter = $schule['schulalter'] . " Jahre";
		$schuljahre = $schule['schuljahre'] . " Jahre";
		$schulstandort = $schule['schulstandort'];
		$schulmonate = $schule['schulmonate'];
		$schulgebiet = $schule['einzugsgebiet'];
		$schulkontinent = $schule['kontinent'];
		

		//ZAHL VON MONAT IN WORT UMWANDELN
		//schulmonate in ein Array, damit im TPL kein 9 auftaucht sondern September. 
		$monate = array('1' => 'Januar', '2' => 'Februar', '3' => 'März', '4' => 'April', '5' => 'Mai', '6' => 'Juni', '7' => 'Juli', '8' => 'August', '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Dezember');
		
		//wir geben hier einen neuen Variablenname für das eben erstellte Phänomen Array umwandeln. Wichtig!
		//$monate aus dem Array $schulmonate aus der oben erstellten Variable, ansonsten gehts nicht!
		$schulmonat_name = $monate[$schulmonate];
		
		//BILDER FÜR KONTINENTE		
		//schulgebiet in ein Array, damit im TPL kein Wort auftaucht sondern ein Bild. 
		$kontinent_bilder = array ('Europa' => '<img src="images/abschluss_europa.png">', 'Asien' => '<img src="/images/abschluss_asien.png">', 'Nordamerika' => '<img src="/images/abschluss_nordamerika.png">', 'Suedamerika' => '<img src="/images/abschluss_suedamerika.png">', 'Australien' => '<img src="/images/abschluss_australien.png">', 'Antarktika' => '<img src="/images/abschluss_antarktika.png">', 'Afrika' => '<img src="/images/abschluss_afrika.png">');
		
		//wir geben hier einen neuen Variablenname für das eben erstellte Phänomen Array umwandeln. Wichtig!
		//$gebietsbild aus dem Array $schulgebiet aus der oben erstellten Variable, ansonsten gehts nicht!
		$gebiet_bild = $kontinent_bilder[$schulkontinent];
		
		
		//laden des Templates
		eval("\$schule_view .= \"" . $templates->get("abschluss_schuleview") . "\";"); //Template für die Einzelausgabe
	}
		
	eval("\$page = \"" . $templates->get("abschluss_schule") . "\";"); //Template für die Gesamtübersicht
	output_page($page);
	
}
?>
