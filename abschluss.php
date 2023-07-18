<?php
define("IN_MYBB", 1);
define('THIS SCRIPT', 'abschluss.php');

require_once "./global.php";
//Wir laden hier die Languages Datei 
$lang->load('abschluss');

	// Format Entries
    require_once MYBB_ROOT."inc/class_parser.php";
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
	if(!$mybb->input['action'])
	{	
	//NAVIGATION BAUEN 
	add_breadcrumb("Abschluss & Einschulung berechnen", "abschluss.php");
	
	//Option für Schule einfügen
	//DATENBANKABFRAGE 
   	$school_query = $db->query("SELECT *
	FROM " . TABLE_PREFIX . "abschluss_schule
	ORDER BY schulname ASC
	");

		while ($row = $db->fetch_array($school_query))
		{
			//Speichert das Gebi Datum oben, nachdem abgeschickt wurde
			if ($row['schulid'] == $mybb->get_input('schule')) {
			$selected = "selected";
			} 
			else {
			$selected = "";
			}
			$select_school .= "<option value='{$row['schulid']}' {$selected}>{$row['schulname']}</option>";
		}


	//für HTML Formular ein Feld-Typ einfügen "date" 
	//speichert das Datum im Format Jahr-Monat-Tag, kann man via explode() splitten
	//verhindert das erstellen von Dropdowns. 
	
	//Lesen des gespeicherten Datums
	$birthdate = $mybb->get_input('birthdate');
	//dates wird eine Liste mit drei Werten dem Jahr (Key0), Monat (Key1) und Tag (Key2)
	$dates = explode("-", $birthdate);
	$jahrberechnung = $dates[0];
	$monatberechnung = $dates[1];
	$tagberechnung = $dates[2];

	//Berechnung beginnt jetzt hier 	
	if ($jahrberechnung !='')
	{
	
		//AUSGABE DES ERGEBNIS FÜR SCHULE 
			$schulangabe = $mybb->get_input('schule');
			
		//Die ID wird in einen Schulnamen für die Ausgabe gewandelt
			$id = $mybb->get_input('schule'); 
			$schulangabe = $db->simple_select("abschluss_schule", "*", "schulid = {$id}");

			$schulabschluss = $db->fetch_array($schulangabe);
			$schulnamen = $schulabschluss['schulname'];
			$einschulalter = $schulabschluss['schulalter'];
			$schuljahre = $schulabschluss['schuljahre'];
			$monatdanach = $schulabschluss['schulmonate'];
			$gleichesjahr = $schulabschluss['gleichesjahr'];

	
		//EINSCHULUNG BERECHNEN
		if ($monatberechnung >= $monatdanach) {
			$einschulung = $jahrberechnung + $einschulalter +1;
		}
		else {
			$einschulung = $jahrberechnung + $einschulalter;
		}

		//Abschluss
		if ($monatberechnung >= $monatdanach) {
			$abschluss = $einschulung + $schuljahre;
		}
			if ($gleichesjahr == 1 && (int) $monatberechnung >= $monatdanach) {
				$abschluss = $einschulung + $schuljahre - 1;
			} 
			else {
			$abschluss = $einschulung + $schuljahre;
			}
	}
	else
	{
		$einschulung ='Bitte trage ein Geburtstag ein und wähle die Schule aus.';
		$tagberechnung ='';
		$monatberechnung ='';
		$jahrberechnung ='';
		$optionschool ='keine Schule ausgewählt';
		$abschluss ='Bitte trage ein Geburtstag ein und wähle die Schule aus.';
	}
   
    eval("\$page = \"".$templates->get("abschluss_main")."\";");
    output_page($page);
}


//Die Unterseite für die Schulen erstellen
if($mybb->input['action'] == "schools") {

 //NAVIGATION BAUEN 
add_breadcrumb("Abschluss & Einschulung berechnen", "abschluss.php");
add_breadcrumb("Schulübersicht");

    //DATENBANKABFRAGE 
    $sql_schule = "SELECT * FROM " . TABLE_PREFIX . "abschluss_schule";
    $query_schule = $db->query($sql_schule);
	
    while ($schule = $db->fetch_array($query_schule)){
			
		// LEER LAUFEN LASSEN, SONST STEHEN INHALTE VON VORHER DRINNEN VOR ALLEM BEI PHP 8 BEACHTEN!!!
		$schulname ="";
		$schulalter ="";
		$schuljahre ="";
		$schulmonate ="";	
		$schulstandort ="";

		//MIT INFOS FÜLLEN 
		$schulname = $schule['schulname']; //Benennung des Titels
		$schuldesc = $parser->parse_message($schule['schuldesc'], $parser_options);
		$schulalter = $schule['schulalter'];
		$schuljahre = $schule['schuljahre'];	
		$schulstandort = $schule['schulstandort'];
		$schulmonate = $schule['schulmonate'];

		//schulmonate in ein Array, damit im TPL kein 9 auftaucht sondern September. 
		$monate = array('1' =>'Januar', '2' =>'Februar', '3' =>'März', '4' =>'April', '5' =>'Mai', '6' =>'Juni', '7' =>'Juli', '8' =>'August', '9' =>'September', '10' =>'Oktober', '11' =>'November', '12' =>'Dezember');
		

		//laden des Templates
		eval("\$schule_view .= \"" . $templates->get("abschluss_schuleview") . "\";"); //Template für die Einzelausgabe
	}
    eval("\$page = \"".$templates->get("abschluss_schule")."\";"); //Template für die Gesamtübersicht
	output_page($page);   

    
}



?>
