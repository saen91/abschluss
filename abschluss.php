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
			$select_school .= "<option value='{$row['schulid']}'>{$row['schulname']}</option>";
		}
		
	//Option für Tag einfügen. 
	$optionsday = array ('Tag', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31');

		foreach ($optionsday as $optionday) {
			{
				$select_day .= "<option value='{$optionday}'>{$optionday}</option>";
			}
		}

	//Option für Monat einfügen. 
	$optionsmonth = array ('Monat', '01' =>'Januar', '02' =>'Februar', '03' =>'März', '04' =>'April', '05' =>'Mai', '06' =>'Juni', '07' =>'Juli', '08' =>'August', '09' =>'September', '10' =>'Oktober', '11' =>'November', '12' =>'Dezember');
			
		foreach ($optionsmonth as $monthNr => $optionmonth ) {
			{
				$select_month .= "<option value='{$monthNr}'>{$optionmonth}</option>";
			}
		}
	
	//Berechnung beginnt jetzt hier 	
	$jahrberechnung = $mybb->get_input('jahrberechnung');
	if ($jahrberechnung !='')
{
	
		//AUSGABE DES ERGEBNIS
			$tagberechnung = $mybb->get_input('tagberechnung');
			$monatberechnung = $mybb->get_input('monatberechnung');		
			$jahrberechnung = $mybb->get_input('jahrberechnung');
			$schulangabe = $mybb->get_input('schule');
			
		//Die ID wird in einen Schulnamen für die Ausgabe gewandelt
			$id = $mybb->get_input('schule'); 
			

			$schulangabe = $db->simple_select("abschluss_schule", "*", "schulid = {$id}");

			while ($schulabschluss = $db->fetch_array($schulangabe))
			{
				$schulnamen = $schulabschluss['schulname'];
				$einschulalter = $schulabschluss['schulalter'];
				$schuljahre = $schulabschluss['schuljahre'];
				$monatdanach = $schulabschluss['schulmonate'];
			}

			// Abschluss noch im gleichen Jahr? wenn eins dann ja
			$gleichesjahr = 1;
	
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
		elseif ($gleichesjahr = 1){
			$abschluss = $einschulung + $schuljahre -1;
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
