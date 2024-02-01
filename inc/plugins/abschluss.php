<?php
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );

//Direkten Zugriff auf diese Datei aus Sicherheitsgründen nicht zulassen
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


//Informationen zum Plugin
function abschluss_info()
{
	global $lang;
	$lang->load('abschluss');

	return array(
		'name' => $lang->abschluss_name,
		'description' => $lang->abschluss_desc_acp,
		'author' => "saen",
		'authorsite' => "https://github.com/saen91",
		'version' => "1.0",
		'compatibility' => "18*"
	);
}

// Diese Funktion installiert das Plugin
function abschluss_install()
{
	global $db, $cache, $mybb;


	//LEGE TABELLE AN für Schulen
	$db->write_query("CREATE TABLE `" . TABLE_PREFIX . "abschluss_schule` (
	`schulid` int(11) NOT NULL  AUTO_INCREMENT,	
	`kontinent` varchar(500) CHARACTER SET utf8 NOT NULL,
	`schulname` varchar(500) CHARACTER SET utf8 NOT NULL,	
	`schuldesc` longtext CHARACTER SET utf8 NOT NULL,
	`schulalter` varchar(140) NOT NULL,
	`schuljahre` int(11)  NOT NULL,
	`schulmonate` int(11)  NOT NULL,
	`gleichesjahr` tinyint NOT NULL,
	`schulstandort` varchar(500) CHARACTER SET utf8 NOT NULL,
	`einzugsgebiet` varchar(500) CHARACTER SET utf8 NOT NULL,
	PRIMARY KEY (`schulid`)
	) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");


	// EINSTELLUNGEN anlegen - Gruppe anlegen
	$setting_group = array(
		'name' => 'abschluss',
		'title' => 'Abschlussjahr berechnen',
		'description' => 'Einstellungen für das hinzufügen von Schulen',
		'disporder' => 1,
		'isdefault' => 0
	);

	$gid = $db->insert_query("settinggroups", $setting_group);

	//Die dazugehörigen einstellungen
	$setting_array = array(

		// Einstellungen, ob Schulbeschreibungen angezeigt werden sollen
		'abschluss_schuldesc' => array(
			'title' => 'Schulbeschreibung',
			'description' => 'Sollen bei den Schulen Informationen angezeigt werden?',
			'optionscode' => 'yesno',
			'value' => '1',
			// Default
			'disporder' => 1
		),
	);


	foreach ($setting_array as $name => $setting) {
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}

	rebuild_settings();

	// Template hinzufügen:
	$insert_array = array(
		'title' => 'abschluss_main',
		'template' => $db->escape_string('<html>
		<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->titeltpl} </title>
		{$headerinclude}</head>
		<body>
		{$header}
			<table width="100%" cellspacing="5" cellpadding="5"  class="trow2">
				<tr>
					{$menu}
					<td valign="top" class="trow1">
						<div style="text-align: justify; width: 70%; margin: 20px auto;">
							<center>
								{$lang->abschluss_welcome} 
								<br>
								<br>
								<a href="abschluss.php?action=schools">{$lang->schoolview}</a>
							</center>							
							{$schulen}
						</div>
					</td>
				</tr>
				
				<tr>
					<td style="text-align:center;">
						<form method="get" id="berechnen" action ="/abschluss.php">
							<input type="hidden" name="action" value="" width="10px">
							<select name="schule">
								{$select_school}
							</select>
							<input type="date" name="birthdate" value="{$mybb->input[\'birthdate\']}" \>	
							<br><br><input type ="submit" value="Einschulung & Abschluss berechnen" class="button">
						</form>
					</td>
				</tr>
			</table>
			
			{$berechnet}
						
			
		{$footer}
		</body>
		</html>'),
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'abschluss_schule',
		'template' => $db->escape_string('<html>
		<head>
			<title>{$mybb->settings[\'bbname\']} - {$lang->schultitel}</title>
			{$headerinclude}
		</head>
		<body>
				{$header}
				<div class="abschlussdescr">{$lang->schuldesc}</div>
				<div class="abschlussfilter">
					<!-- Beispiel eines Filters ohne JavaScript mit PHP und Formular -->
					<form  id="filter_kontinent" method="get" action="abschluss.php">
						<input type="hidden" name="action" value="schools">
					  	<label for="filter_kontinent">{$lang->kontiaus}</label>
					  		<select name="filter_kontinent" id="kontinent-filter">
								<option value="">{$lang->allkonti}</option>
								<option value="europa">Europa</option>
								<option value="asien">Asien</option>
								<option value="afrika">Afrika</option>
								<option value="australien">Australien</option>
								<option value="antarktika">Antarktika</option>
								<option value="nordamerika">Nordamerika</option>
								<option value="suedamerika">Südamerika</option>
					  		</select>
					  	<input type="submit" id="submit" value="Filtern" class="button">
					</form>
				
					
					</div>
				{$schule_view}

		{$footer}
		</body>
	</html>'),
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'abschluss_schuleview',
		'template' => $db->escape_string('<div id="abschlussschule">
		<div class="schulkonti">{$gebiet_bild}</div>
		  <div class="schulinfo">
			<div class="schulname">
				<center>{$schulname}</center>
			</div>
			<div class="schulfacts">
				<div class="standort">
					<div class="standorthead"><h2>{$lang->location}</h2></div>
					<div class="standortvari">{$schulstandort}</div>
				</div>
				<div class="schulalter">
					<div class="schulalterhead"><h2>{$lang->einschulalter}</h2></div>
					<div class="schulaltervari">{$schulalter}</div>
				</div>
				<div class="schuljahre">
					<div class="schuljahrehead"><h2>{$lang->schuljahre}</h2></div>
					<div class="schuljahrevari">{$schuljahre}</div>
				</div>
				<div class="stichtag">
					<div class="stichtaghead"><h2>{$lang->stich}</h2></div>
					<div class="stichtagvari">{$schulmonat_name}</div>
				</div>
				<div class="einzugsgebiet">
					<div class="gebiethead"><h2>{$lang->einzug}</h2></div>
					<div class="gebietvari">{$schulgebiet}</div>
				</div>
			  </div>
		</div>
	
		  <div class="schuldescr">
			  <div class="schuldescrhead"><h2>{$lang->schuldesc2}</h2></div>
			<div class="schuldescrvari">{$schuldesc}</div>
		  </div>
	</div>'),
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);


	$insert_array = array(
		'title' => 'abschluss_berechnet',
		'template' => $db->escape_string('<div class="abschlussberechnen">
		<div class="berechnenhead"><h1>{$lang->ergebnis} </h1></div>
		  <div class="bgebi">
			  <div class="bgebihead"><h2>{$lang->gebi} </h2></div>
			<div class="bgebivari">{$tagberechnung}.{$monatberechnung}.{$jahrberechnung}</div>
		  </div>
		  <div class="bschool">
			<div class="bschoolhead"><h2>{$lang->school} </h2></div>
			<div class="bschollvari">{$schulnamen} </div>
		  </div>
		  <div class="beinschulalter">
			<div class="beinschulalterhead"><h2>{$lang->einschulalter}</h2></div>
			<div class="beinschulaltervari">{$einschulalter}</div>
		  </div>
		  <div class="bschuljahre">
			<div class="bschuljahrehead"><h2>{$lang->schuljahre}</h2></div>
			<div class="bschuljahrevari">{$schuljahre}</div>
		  </div>
		<div class="babschlusshead"><h1>{$lang->schoolinfo}</h1></div>
		  <div class="beinschul">
			<div class="beinschulhead"><h2>{$lang->einschulung}</h2></div>
			<div class="beinschulvari">{$einschulung}</div>
		  </div>
		  <div class="babschluss">
			<div class="babschlussahead"><h2>{$lang->abschluss}</h2></div>
			<div class="babschlussvari">{$abschluss}</div>
		  </div>
		  <div class="bandere">
			<div class="banderehead"><h2>{$lang->andere}</h2></div>
			<div class="banderevari">{$schulpersonen} </div>
		  </div>
	</div>'),
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	
	//CSS hinzufügen 
	  $css = array(
    'name' => 'abschluss.css',
    'tid' => 1,
    'attachedto' => '',
    "stylesheet" =>    '.abschlussdescr {
		text-align:justify;
	}
	
	.abschlussfilter {
		margin-top: 20px;
		text-align:center;
	}
	
	/* For mobile phones: */
			#abschlussschule {
			display: grid; 
			grid-template-columns: 1fr 1fr; 
			grid-template-rows: auto; 
			gap: 10px 10px; 
			grid-template-areas: 
			"schulkonti schulinfo "	
			"schuldescr schuldescr"; 
			margin-top: 50px;
	}
	
	@media only screen and (min-width: 1400px) {
		  /* For desktop: */
			#abschlussschule {
			display: grid; 
			grid-template-columns: 1fr 1fr 1fr; 
			grid-template-rows: auto; 
			gap: 10px 10px; 
			grid-template-areas: 
			"schulkonti schulinfo schuldescr"; 
			margin-top: 20px;
		}
	}
	
	.schulkonti { grid-area: schulkonti;  margin: auto;}
	
		.schulinfo {
		  display: grid; 
		  grid-template-columns: 1fr; 
		  grid-template-rows: 0.5fr 1.5fr; 
		  gap: 0px 0px; 
		  grid-template-areas: 
			"schulname"
			"schulfacts"; 
		  grid-area: schulinfo; 
	}
	
	.schulname {
		  display: grid; 
		  grid-template-columns: 1fr; 
		  grid-template-rows: 1fr; 
		  gap: 0px 0px; 
		  grid-template-areas: 
		".";  
		  grid-area: schulname; 
	}
	
	.schulfacts {
		  display: grid; 
		  grid-template-columns: 1fr 1fr; 
		  grid-template-rows: 1fr 1fr 1fr; 
		  gap: 10px 10px; 
		  grid-template-areas: 
			"standort schulalter"
			"schuljahre stichtag"
			"einzugsgebiet einzugsgebiet"; 
		  grid-area: schulfacts; 
	}
	
	.standort {
	  display: grid; 
	  grid-template-columns: 1fr; 
	  grid-template-rows: 1fr 1fr; 
	  gap: 0px 0px; 
	  grid-template-areas: 
		"standorthead"
		"standortvari"; 
	  grid-area: standort; 
	}
	.standorthead { grid-area: standorthead; }
	.standortvari { grid-area: standortvari; }
	
	.schulalter {
	  display: grid; 
	  grid-template-columns: 1fr; 
	  grid-template-rows: 1fr 1fr; 
	  gap: 0px 0px; 
	  grid-template-areas: 
		"schulalterhead"
		"schulaltervari"; 
	  grid-area: schulalter; 
	}
	.schulalterhead { grid-area: schulalterhead; }
	.schulaltervari { grid-area: schulaltervari; }
	
	.schuljahre {
	  display: grid; 
	  grid-template-columns: 1fr; 
	  grid-template-rows: 1fr 1fr; 
	  gap: 0px 0px; 
	  grid-template-areas: 
		"schuljahrehead"
		"schuljahrevari"; 
	  grid-area: schuljahre; 
	}
	.schuljahrehead { grid-area: schuljahrehead; }
	.schuljahrevari { grid-area: schuljahrevari; }
	
	.stichtag {
	  display: grid; 
	  grid-template-columns: 1fr; 
	  grid-template-rows: 1fr 1fr; 
	  gap: 0px 0px; 
	  grid-template-areas: 
		"stichtaghead"
		"stichtagvari"; 
	  grid-area: stichtag; 
	}
	.stichtaghead { grid-area: stichtaghead; }
	.stichtagvari { grid-area: stichtagvari; }
	
	.einzugsgebiet {
	  display: grid; 
	  grid-template-columns: 1fr; 
	  grid-template-rows: 1fr 1fr; 
	  gap: 0px 0px; 
	  grid-template-areas: 
		"gebiethead"
		"gebietvari"; 
	  grid-area: einzugsgebiet; 
	}
	.gebiethead { grid-area: gebiethead; }
	.gebietvari { grid-area: gebietvari; }
	
	.schuldescr {
		  display: grid; 
		  grid-template-columns: 1fr; 
		  grid-template-rows: 0.5fr 1.5fr; 
		  gap: 0px 0px; 
		  grid-template-areas: 
			"schuldescrhead"
			"schuldescrvari"; 
		  grid-area: schuldescr; 
	}
	.schuldescrhead { grid-area: schuldescrhead; margin: auto 0px;}
	.schuldescrvari { grid-area: schuldescrvari; height:200px; overflow:auto; text-align:justify;padding-right:10px;}
	
	
	/* SCHULBERECHNENÜBERSICHT */
	
	.abschlussberechnen {  display: grid;
	  grid-template-columns: 1fr 1fr;
	  grid-template-rows: auto auto auto auto auto auto;
	  gap: 10px 10px;
	  grid-auto-flow: row;
	  grid-template-areas:
		"berechnenhead berechnenhead"
		"bgebi bschool"
		"beinschulalter bschuljahre"
		"babschlusshead babschlusshead"
		"beinschul babschluss"
		"bandere bandere";
	}
	
	.berechnenhead { grid-area: berechnenhead; }
	
	.bgebi {  display: grid;
	  grid-template-columns: 1fr;
	  grid-template-rows: auto auto;
	  gap: 0px 0px;
	  grid-auto-flow: row;
	  grid-template-areas:
		"bgebihead"
		"bgebivari";
	  grid-area: bgebi;
	}
	
	.bgebihead { grid-area: bgebihead; }
	
	.bgebivari { grid-area: bgebivari; }
	
	.bschool {  display: grid;
	  grid-template-columns: 1fr;
	  grid-template-rows: auto auto;
	  gap: 0px 0px;
	  grid-auto-flow: row;
	  grid-template-areas:
		"bschoolhead"
		"bschollvari";
	  grid-area: bschool;
	}
	
	.bschoolhead { grid-area: bschoolhead; }
	
	.bschollvari { grid-area: bschollvari; }
	
	.beinschulalter {  display: grid;
	  grid-template-columns: 1fr;
	  grid-template-rows: auto auto;
	  gap: 0px 0px;
	  grid-auto-flow: row;
	  grid-template-areas:
		"beinschulalterhead"
		"beinschulaltervari";
	  grid-area: beinschulalter;
	}
	
	.beinschulalterhead { grid-area: beinschulalterhead; }
	
	.beinschulaltervari { grid-area: beinschulaltervari; }
	
	.bschuljahre {  display: grid;
	  grid-template-columns: 1fr;
	  grid-template-rows: auto auto;
	  gap: 0px 0px;
	  grid-auto-flow: row;
	  grid-template-areas:
		"bschuljahrehead"
		"bschuljahrevari";
	  grid-area: bschuljahre;
	}
	
	.bschuljahrehead { grid-area: bschuljahrehead; }
	
	.bschuljahrevari { grid-area: bschuljahrevari; }
	
	.babschlusshead { grid-area: babschlusshead; }
	
	.beinschul {  display: grid;
	  grid-template-columns: 1fr;
	  grid-template-rows: auto auto;
	  gap: 0px 0px;
	  grid-auto-flow: row;
	  grid-template-areas:
		"beinschulhead"
		"beinschulvari";
	  grid-area: beinschul;
	}
	
	.beinschulhead { grid-area: beinschulhead; }
	
	.beinschulvari { grid-area: beinschulvari; }
	
	.babschluss {  display: grid;
	  grid-template-columns: 1fr;
	  grid-template-rows: auto auto;
	  gap: 0px 0px;
	  grid-auto-flow: row;
	  grid-template-areas:
		"babschlussahead"
		"babschlussvari";
	  grid-area: babschluss;
	}
	
	.babschlussahead { grid-area: babschlussahead; }
	
	.babschlussvari { grid-area: babschlussvari; }
	
	.bandere {  display: grid;
	  grid-template-columns: 1fr;
	  grid-template-rows: auto auto;
	  gap: 0px 0px;
	  grid-auto-flow: row;
	  grid-template-areas:
		"banderehead"
		"banderevari";
	  grid-area: bandere;
	}
	
	.banderehead { grid-area: banderehead; }
	
	.banderevari { grid-area: banderevari; column-count: 2;}
	',
    'cachefile' => $db->escape_string(str_replace('/', '', 'abschluss.css')),
    'lastmodified' => time()
  );
	
	 require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

  $sid = $db->insert_query("themestylesheets", $css);
  $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

  $tids = $db->simple_select("themes", "tid");
  while ($theme = $db->fetch_array($tids)) {
    update_theme_stylesheet_list($theme['tid']);
  }
	
	
	
}

//INSTALLIEREN VOM PLUGIN - liefert true zurück, wenn Plugin installiert. Sonst false
function abschluss_is_installed()
{
	global $db, $mybb;

	if ($db->table_exists("abschluss_schule")) {
		return true;
	}
	return false;
}

//DEINSTALLIEREN VOM PLUGIN
function abschluss_uninstall()
{
	global $db;
	//Datenbank-Eintrag löschen
	if ($db->table_exists("abschluss_schule")) {
		$db->drop_table("abschluss_schule");
	}

	//Einstellungen deinstallieren:
	$db->query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name='abschluss'"); //Gruppe löschen
	$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='abschluss_schuldesc'"); //Einzel-Einstellung löschen

	rebuild_settings();

	//Templates löschen:
	$db->delete_query("templates", "title LIKE '%abschluss%'");
	
	//CSS LÖSCHEN
	  require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
	  $db->delete_query("themestylesheets", "name = 'abschluss.css'");
	  $query = $db->simple_select("themes", "tid");
	  while ($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	  }

}

//AKTIVIEREN VOM PLUGIN - bspw. variablen einfügen für den Balken
function abschluss_activate()
{
	global $db, $cache;
	require MYBB_ROOT . "/inc/adminfunctions_templates.php";

	//welches Template, welche variable wird gesucht, welche soll eingesetzt werden und wie sieht es dann aus?
	//find_replace_templatesets('header', '#'.preg_quote('{$bbclosedwarning}').'#', '{$new_applicantstop} {$bbclosedwarning}');//

}

//DEAKTIVIEREN VOM PLUGIN - bspw. variablen entfernen für den Balken
function abschluss_deactivate()
{
	global $db, $cache;
	require MYBB_ROOT . "/inc/adminfunctions_templates.php";
	//Variable wieder aus TPL entfernen.
	// find_replace_templatesets("header", "#".preg_quote('{$new_applicantstop}')."#i", '', 0);//

}


// DIE GANZE MAGIE!
$plugins->add_hook('global_start', 'abschluss_global');
//Für den Balken!
//damit der auch funktioniert, siehe auch add_entry... 
function abschluss_global()
{
	global $db, $mybb, $templates, $lang, $action_file;
	$lang->load('abschluss');

	//Action Baum bauen
	$mybb->input['action'] = $mybb->get_input('action');

}


// Admin CP konfigurieren - 
//Action Handler erstellen
$plugins->add_hook("admin_config_action_handler", "abschluss_admin_config_action_handler");

function abschluss_admin_config_action_handler(&$actions)
{
	$actions['abschluss'] = array('active' => 'abschluss', 'file' => 'abschluss');
}

//ACP Menüpunkt unter Konfigurationen erstellen
$plugins->add_hook("admin_config_menu", "abschluss_admin_config_menu");
function abschluss_admin_config_menu(&$sub_menu)
{
	$sub_menu[] = [
		"id" => "abschluss",
		"title" => "Schulen für Abschluss verwalten",
		"link" => "index.php?module=config-abschluss"
	];
}

// Schulen hinzufügen im ACP!
$plugins->add_hook("admin_load", "abschluss_manage_abschluss");
function abschluss_manage_abschluss()
{
	global $mybb, $db, $lang, $page, $run_module, $action_file;
	$lang->load('abschluss');

	if ($page->active_action != 'abschluss') {
		return false;
	}

	if ($run_module == 'config' && $action_file == "abschluss") {

		//Schul Übersicht 
		if ($mybb->input['action'] == "" || !isset($mybb->input['action'])) {
			// Add a breadcrumb - Navigation Seite 
			$page->add_breadcrumb_item($lang->abschluss_manage);

			//Header Auswahl Felder im Aufnahmestop verwalten Menü hinzufügen
			$page->output_header($lang->abschluss_manage . " - " . $lang->abschluss_overview);

			//Übersichtsseite über alle Schulen
			$sub_tabs['abschluss'] = [
				"title" => $lang->abschluss_overview_entries,
				"link" => "index.php?module=config-abschluss",
				"description" => $lang->abschluss_overview_entries_desc
			];

			//Neue Schule hinterlegen, Button
			$sub_tabs['abschluss_entry_add'] = [
				"title" => $lang->abschluss_add_entry,
				"link" => "index.php?module=config-abschluss&amp;action=add_entry",
				"description" => $lang->abschluss_add_entry_desc
			];

			$page->output_nav_tabs($sub_tabs, 'abschluss');

			// Zeige Fehler an
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}

			//Übersichtsseite erstellen 
			$form = new Form("index.php?module=config-abschluss", "post");

			//Die Überschriften!
			$form_container = new FormContainer("<div style=\"text-align: center;\">$lang->abschluss_overview_titel_titel</div>");
			//Bezeichnung der Schule
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->abschluss_overview_titel_name</div>");
			//Beschreibung der Schule
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->abschluss_overview_titel_desc</div>");
			//Ab welchem Alter wird man eingeschult
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->abschluss_overview_titel_alter</div>");
			//Wie viele Jahrgänge besucht man?
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->abschluss_overview_titel_jahre</div>");
			//Ab welchem Monat wird man ein Jahr später eingeschult?
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->abschluss_overview_titel_monat</div>");
			//Wo befindet sich die Schule?
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->abschluss_overview_titel_standort</div>");
			//Auf welchem Kontinent?
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->abschluss_overview_titel_kontinent</div>");
			//Optionen
			$form_container->output_row_header($lang->abschluss_options, array('style' => 'text-align: center; width: 5%;'));

			//Alle bisherigen Einträge herbeiziehen und nach Schulname sortieren
			$query = $db->simple_select(
				"abschluss_schule",
				"*",
				"",
				["order_by" => 'schulname', 'order_dir' => 'ASC']
			);

			while ($abschluss_schule = $db->fetch_array($query)) {

				//Gestaltung der Übersichtsseite, Infos die angezeigt werden 
				//Schulname
				$form_container->output_cell('<strong>' . htmlspecialchars_uni($abschluss_schule['schulname']) . '</strong>');
				//Schulbeschreibung
				$form_container->output_cell('<strong>' . htmlspecialchars_uni($abschluss_schule['schuldesc']) . '</strong>');
				//Einschulalter
				$form_container->output_cell('<strong>' . htmlspecialchars_uni($abschluss_schule['schulalter']) . '</strong>');
				//Wie viele Jahre besucht man die Schule?
				$form_container->output_cell('<strong>' . htmlspecialchars_uni($abschluss_schule['schuljahre']) . '</strong>');
				//Ab welchem Monat wird man ein Jahr später eingeschult?
				$form_container->output_cell('<strong>' . htmlspecialchars_uni($abschluss_schule['schulmonate']) . '</strong>');
				//Wo befindet sich die Schule?
				$form_container->output_cell('<strong>' . htmlspecialchars_uni($abschluss_schule['schulstandort']) . '</strong>');
				//Kontinent?
				$form_container->output_cell('<strong>' . htmlspecialchars_uni($abschluss_schule['kontinent']) . '</strong>');

				//Pop Up für Bearbeiten & Löschen
				$popup = new PopupMenu("abschluss_{$abschluss_schule['schulid']}", $lang->abschluss_options);
				$popup->add_item(
					$lang->abschluss_edit,
					"index.php?module=config-abschluss&amp;action=edit_entry&amp;schulid={$abschluss_schule['schulid']}"
				);
				$popup->add_item(
					$lang->abschluss_delete,
					"index.php?module=config-abschluss&amp;action=delete_entry&amp;stopid={$abschluss_schule['schulid']}"
					. "&amp;my_post_key={$mybb->post_code}"
				);
				$form_container->output_cell($popup->fetch(), array("class" => "align_center"));
				$form_container->construct_row();
			}

			$form_container->end();
			$form->end();
			$page->output_footer();

			exit;
		}

		if ($mybb->input['action'] == "add_entry") {
			if ($mybb->request_method == "post") {

				// Prüfen, ob erforderliche Felder nicht leer sind
				if (empty($mybb->input['kontinent'])) {
					$errors[] = $lang->abschluss_error_kontinent;
				}
				
				if (empty($mybb->input['schulname'])) {
					$errors[] = $lang->abschluss_error_titel;
				}

				if (empty($mybb->input['schulalter'])) {
					$errors[] = $lang->abschluss_error_alter;
				}

				if (empty($mybb->input['schuljahre'])) {
					$errors[] = $lang->abschluss_error_jahre;
				}

				if (empty($mybb->input['schulmonate'])) {
					$errors[] = $lang->abschluss_error_monate;
				}

				if (empty($mybb->input['schulstandort'])) {
					$errors[] = $lang->abschluss_error_standort;
				}
				if (empty($mybb->input['einzugsgebiet'])) {
					$errors[] = $lang->abschluss_error_einzugsgebiet;
				}

				// keine Fehler - dann einfügen
				if (empty($errors)) {

					$new_entry = array(
						"schulid" => (int) $mybb->input['schulid'],
						"kontinent" => $db->escape_string($mybb->input['kontinent']),
						"schulname" => $db->escape_string($mybb->input['schulname']),
						"schuldesc" => $db->escape_string($mybb->input['schuldesc']),
						"schulalter" => $db->escape_string($mybb->input['schulalter']),
						"schuljahre" => $db->escape_string($mybb->input['schuljahre']),
						"schulmonate" => $db->escape_string($mybb->input['schulmonate']),
						"schulstandort" => $db->escape_string($mybb->input['schulstandort']),
						"gleichesjahr" => (int) $mybb->input['gleichesjahr'],						
						"einzugsgebiet" => $db->escape_string($mybb->input['einzugsgebiet']),
					);
					$db->insert_query("abschluss_schule", $new_entry);

					$mybb->input['module'] = "abschluss";
					$mybb->input['action'] = $lang->abschluss_add_entry_solved;
					log_admin_action(htmlspecialchars_uni($mybb->input['schulname']));

					flash_message($lang->abschluss_add_entry_solved, 'success');
					admin_redirect("index.php?module=config-abschluss");
				}
			}

			$page->add_breadcrumb_item($lang->abschluss_add_entry);

			// Editor scripts
			$page->extra_header .= <<<EOF
                
<link rel="stylesheet" href="../jscripts/sceditor/themes/mybb.css" type="text/css" media="all" />
<script type="text/javascript" src="../jscripts/sceditor/jquery.sceditor.bbcode.min.js?ver=1832"></script>
<script type="text/javascript" src="../jscripts/bbcodes_sceditor.js?ver=1832"></script>
<script type="text/javascript" src="../jscripts/sceditor/plugins/undo.js?ver=1832"></script> 
EOF;

			// Build options header
			$page->output_header($lang->abschluss_manage . " - " . $lang->abschluss_overview);

			//Übersichtsseite über alle Schulen
			$sub_tabs['abschluss'] = [
				"title" => $lang->abschluss_overview_entries,
				"link" => "index.php?module=config-abschluss",
				"description" => $lang->abschluss_overview_entries_desc
			];

			//Neuen Stop hinterlegen, Button
			$sub_tabs['abschluss_entry_add'] = [
				"title" => $lang->abschluss_add_entry,
				"link" => "index.php?module=config-abschluss&amp;action=add_entry",
				"description" => $lang->abschluss_add_entry_desc
			];

			$page->output_nav_tabs($sub_tabs, 'abschluss_entry_add');

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}

			// Erstellen der "Formulareinträge"
			$form = new Form("index.php?module=config-abschluss&amp;action=add_entry", "post", "", 1);
			$form_container = new FormContainer($lang->abschluss_add);

			$form_container->output_row(
				$lang->abschluss_form_name . "<em>*</em>",
				$lang->abschluss_form_name_desc,
				$form->generate_text_box('schulname', $mybb->input['schulname'])
			);

			$text_editor = $form->generate_text_area(
				'schuldesc', $mybb->input['schuldesc'],
				array(
					'id' => 'schuldesc',
					'rows' => '25',
					'cols' => '70',
					'style' => 'height: 150px; width: 75%'
				)
			);

			$text_editor .= build_mycode_inserter('schuldesc');
			$form_container->output_row(
				$lang->abschluss_form_desc,
				$lang->abschluss_form_desc_desc,
				$text_editor,
				'text'
			);

			$form_container->output_row(
				$lang->abschluss_form_alter . "<em>*</em>",
				$lang->abschluss_form_alter_desc,
				$form->generate_text_box('schulalter', $mybb->input['schulalter'])
			);

			$form_container->output_row(
				$lang->abschluss_form_jahre . "<em>*</em>",
				$lang->abschluss_form_jahre_desc,
				$form->generate_text_box('schuljahre', $mybb->input['schuljahre'])
			);

			$form_container->output_row(
				$lang->abschluss_form_monate . "<em>*</em>",
				$lang->abschluss_form_monate_desc,
				$form->generate_text_box('schulmonate', $mybb->input['schulmonate'])
			);
			
			$form_container->output_row(
				$lang->abschluss_form_kontinent . "<em>*</em>",
				$lang->abschluss_form_kontinent_desc,
				$form->generate_text_box('kontinent', $mybb->input['kontinent'])
			);

			$form_container->output_row(
				$lang->abschluss_form_standort . "<em>*</em>",
				$lang->abschluss_form_standort_desc,
				$form->generate_text_box('schulstandort', $mybb->input['schulstandort'])
			);
			$form_container->output_row(
				$lang->abschluss_form_einzugsgebiet . "<em>*</em>",
				$lang->abschluss_form_einzugsgebiet_desc,
				$form->generate_text_box('einzugsgebiet', $mybb->input['einzugsgebiet'])
			);
			
			$form_container->output_row(
				$lang->abschluss_form_gleichesjahr,
				$lang->abschluss_form_gleichesjahr_desc,
				$form->generate_select_box(
					'gleichesjahr',
					[0 => "Nein", 1 => "Ja"],
					'',
					['id' => 'gleichesjahr']
				),
				'gleichesjahr'
			);

			$form_container->end();
			$buttons[] = $form->generate_submit_button($lang->abschluss_send);
			$form->output_submit_wrapper($buttons);
			$form->end();
			$page->output_footer();

			exit;
		}

		// Format Entries
require_once MYBB_ROOT . "inc/class_parser.php";
$parser = new postParser;
$parser_options = array(
	"allow_html" => 1,
	"allow_mycode" => 1,
	"allow_smilies" => 1,
	"allow_imgcode" => 1
);


		if ($mybb->input['action'] == "edit_entry") {
			if ($mybb->request_method == "post") {


				// Prüfen, ob erforderliche Felder nicht leer sind
				if (empty($mybb->input['kontinent'])) {
					$errors[] = $lang->abschluss_error_kontinent;
				}
				if (empty($mybb->input['schulname'])) {
					$errors[] = $lang->abschluss_error_titel;
				}
				if (empty($mybb->input['schulalter'])) {
					$errors[] = $lang->abschluss_error_alter;
				}
				if (empty($mybb->input['schuljahre'])) {
					$errors[] = $lang->abschluss_error_jahre;
				}
				if (empty($mybb->input['schulmonate'])) {
					$errors[] = $lang->abschluss_error_monate;
				}
				if (empty($mybb->input['schulstandort'])) {
					$errors[] = $lang->abschluss_error_standort;
				}
				if (empty($mybb->input['einzugsgebiet'])) {
					$errors[] = $lang->abschluss_error_einzugsgebiet;
				}

				// No errors - insert the terms of use
				if (empty($errors)) {
					$schulid = $mybb->get_input('schulid', MyBB::INPUT_INT);


					$edited_entry = [
						"schulid" => (int) $mybb->input['schulid'],
						"kontinent" => $db->escape_string($mybb->input['kontinent']),
						"schulname" => $db->escape_string($mybb->input['schulname']),
						"schuldesc" => $db->escape_string($mybb->input['schuldesc']),
						"schulalter" => $db->escape_string($mybb->input['schulalter']),
						"schuljahre" => $db->escape_string($mybb->input['schuljahre']),
						"schulmonate" => $db->escape_string($mybb->input['schulmonate']),
						"schulstandort" => $db->escape_string($mybb->input['schulstandort']),
						"gleichesjahr" => (int) $mybb->input['gleichesjahr'],
						"einzugsgebiet" => $db->escape_string($mybb->input['einzugsgebiet'])
					];

					$db->update_query("abschluss_schule", $edited_entry, "schulid='{$schulid}'");

					$mybb->input['module'] = "abschluss";
					$mybb->input['action'] = $lang->abschluss_edit_entry_solved;
					log_admin_action(htmlspecialchars_uni($mybb->input['schulname']));

					flash_message($lang->abschluss_edit_entry_solved, 'success');
					admin_redirect("index.php?module=config-abschluss");
				}

			}

			$page->add_breadcrumb_item($lang->abschluss_edit_entry);

			// Editor scripts
			$page->extra_header .= <<<EOF
<link rel="stylesheet" href="../jscripts/sceditor/themes/mybb.css" type="text/css" media="all" />
<script type="text/javascript" src="../jscripts/sceditor/jquery.sceditor.bbcode.min.js?ver=1832"></script>
<script type="text/javascript" src="../jscripts/bbcodes_sceditor.js?ver=1832"></script>
<script type="text/javascript" src="../jscripts/sceditor/plugins/undo.js?ver=1832"></script> 
EOF;

			// Build options header
			$page->output_header($lang->abschluss_manage . " - " . $lang->abschluss_overview);

			$sub_tabs['abschluss'] = [
				"title" => "Schulen Übersicht",
				"link" => "index.php?module=config-abschluss",
				"description" => $lang->abschluss_overview
			];

			$sub_tabs['abschluss_entry_add'] = [
				"title" => "Schule hinzufügen",
				"link" => "index.php?module=config-abschluss&amp;action=add_entry",
				"description" => $lang->abschluss_add_entry_desc
			];
			$sub_tabs['abschluss_entry_edit'] = [
				"title" => "Schule bearbeiten",
				"link" => "index.php?module=config-abschluss&amp;action=edit_entry",
				"description" => $lang->abschluss_edit_entry_desc
			];


			$page->output_nav_tabs($sub_tabs, 'abschluss_entry_edit');

			// Show errors
			if (isset($errors)) {
				$page->output_inline_error($errors);
			}

			// Get the data
			$schulid = $mybb->get_input('schulid', MyBB::INPUT_INT);
			$query = $db->simple_select("abschluss_schule", "*", "schulid={$schulid}");
			$edit_entry = $db->fetch_array($query);

			// Erstellen des "Formulars"
			$form = new Form("index.php?module=config-abschluss&amp;action=edit_entry", "post", "", 1);
			echo $form->generate_hidden_field('schulid', $schulid);

			$form_container = new FormContainer($lang->abschluss_edit_entry);
		
			
			$form_container->output_row(
				$lang->abschluss_form_name . "<em>*</em>",
				$lang->abschluss_form_name_desc,
				$form->generate_text_box('schulname', htmlspecialchars_uni($edit_entry['schulname']))
			);

			$text_editor = $form->generate_text_area(
				'schuldesc',
				htmlspecialchars_uni($edit_entry['schuldesc']),
				array(
					'id' => 'schuldesc' . "<em>*</em>",
					'rows' => '25',
					'cols' => '70',
					'style' => 'height: 150px; width: 75%'
				)
			);

			$text_editor .= build_mycode_inserter('schuldesc');
			$form_container->output_row(
				$lang->abschluss_form_desc . "<em>*</em>",
				$lang->abschluss_form_desc_desc,
				$text_editor,
				'schuldesc'
			);

			$form_container->output_row(
				$lang->abschluss_form_alter . "<em>*</em>",
				$lang->abschluss_form_alter_desc,
				$form->generate_text_box('schulalter', htmlspecialchars_uni($edit_entry['schulalter']))
			);

			$form_container->output_row(
				$lang->abschluss_form_jahre . "<em>*</em>",
				$lang->abschluss_form_jahre_desc,
				$form->generate_text_box('schuljahre', htmlspecialchars_uni($edit_entry['schuljahre']))
			);

			$form_container->output_row(
				$lang->abschluss_form_monate . "<em>*</em>",
				$lang->abschluss_form_monate_desc,
				$form->generate_text_box('schulmonate', htmlspecialchars_uni($edit_entry['schulmonate']))
			);

			$form_container->output_row(
				$lang->abschluss_form_standort . "<em>*</em>",
				$lang->abschluss_form_standort_desc,
				$form->generate_text_box('schulstandort', htmlspecialchars_uni($edit_entry['schulstandort']))
			);		
			
			$form_container->output_row(
				$lang->abschluss_form_kontinent . "<em>*</em>",
				$lang->abschluss_form_kontinent_desc,
				$form->generate_text_box('kontinent', htmlspecialchars_uni($edit_entry['kontinent']))
			);	
			
			$form_container->output_row(
				$lang->abschluss_form_einzugsgebiet . "<em>*</em>",
				$lang->abschluss_form_einzugsgebiet_desc,
				$form->generate_text_box('einzugsgebiet', htmlspecialchars_uni($edit_entry['einzugsgebiet']))
			);

			$form_container->output_row(
				$lang->abschluss_form_gleichesjahr,
				$lang->abschluss_form_gleichesjahr_desc,
				$form->generate_select_box(
					'gleichesjahr',
					[0 => "Nein", 1 => "Ja"],
					$edit_entry['gleichesjahr'],
					['id' => 'gleichesjahr']
				),
				'gleichesjahr'
			);

			$form_container->end();
			$buttons[] = $form->generate_submit_button($lang->abschluss_send);
			$form->output_submit_wrapper($buttons);
			$form->end();
			$page->output_footer();

			exit;
		}
		// Lösche die Schule
		if ($mybb->input['action'] == "delete_entry") {

			// Get data
			$schulid = $mybb->get_input('schulid', MyBB::INPUT_INT);
			$query = $db->simple_select("abschluss_schule", "*", "schulid={$schulid}");
			$del_entry = $db->fetch_array($query);

			// Error Handling
			if (empty($stopid)) {
				flash_message($lang->abschluss_error_option, 'error');
				admin_redirect("index.php?module=config-abschluss");
			}

			// Cancel button pressed?
			if (isset($mybb->input['no']) && $mybb->input['no']) {
				admin_redirect("index.php?module=config-abschluss");
			}

			if (!verify_post_check($mybb->input['my_post_key'])) {
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=config-abschluss");
			}


			// Wenn alles okay ist
			else {
				if ($mybb->request_method == "post") {

					$db->delete_query("abschluss_schule", "schulid='{$schulid}'");

					$mybb->input['module'] = "abschluss";
					$mybb->input['action'] = $lang->abschluss_delete_entry_solved;
					log_admin_action(htmlspecialchars_uni($del_entry['stoptitel']));

					flash_message($lang->abschluss_delete_entry_solved, 'success');
					admin_redirect("index.php?module=config-abschluss");
				} else {

					$page->output_confirm_action(
						"index.php?module=config-abschluss&amp;action=delete_entry&amp;schulid={$schulid}",
						$lang->abschluss_delete_entry_question
					);
				}
			}
			exit;
		}

	}
}
