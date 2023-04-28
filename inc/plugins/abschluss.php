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
	`schulname` varchar(500) CHARACTER SET utf8 NOT NULL,	
	`schuldesc` longtext CHARACTER SET utf8 NOT NULL,
	`schulalter` varchar(140) NOT NULL,
	`schuljahre` int(11)  NOT NULL,
	`schulmonate` int(11)  NOT NULL,
	`schulstandort` varchar(500) CHARACTER SET utf8 NOT NULL,
	PRIMARY KEY (`schulid`)
	) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;");


// EINSTELLUNGEN anlegen - Gruppe anlegen
	$setting_group = array (
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
	    'value' => '1', // Default
	    'disporder' => 1 ),
	    );
			
	
	foreach ($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;
		
		$db->insert_query('settings', $setting);
	}

	rebuild_settings();
	
// Template hinzufügen:
	$insert_array = array(
		'title' => 'abschlussberechnen',
		'template' => $db->escape_string('<html>
<head>
<title>{$settings[\'bbname\']} - Abschluss berechnen</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>Einschulung & Abschluss berechnen</strong></td>
</tr>
<tr>
<td class="trow1">
	<blockquote><center>HIER KOMMT NOCH NE LANGUAGES DATEI HIN<br><br></center>
<table style="margin:auto;">
	<tr>
		<td style="text-align:center;">
			<form method="get" id="berechnen" action ="/abschlussberechnen.php">
				<input type="hidden" name="action" value="berechnung" width="10px">
				<select name="schule">
					Schulauswahl
				</select>
				<select name="tagberechnung">
					Tagauswahl
				</select>
				<select name="monatberechnung">
					Monatauswahl
				</select>
				<input type ="text" class="textbox" name="jahrberechnung">
				<br><br><input type ="submit" value="Einschulung & Abschluss berechnen" class="button">
			</form>
		</td>
	</tr>
</table>
		<br>
		
		<table width="100%">
			<tr>
				<td width="50%">Angegebener Geburtstag:</td><td width="50%"> Hier steht der Geburtstag</td>
			</tr>
			<tr>
				<td width="50%">Ausgewählte Schule:</td><td width="50%"> Hier die Infos zur Schule</td>
			</tr>
			<tr>
				<td width="50%"><h2>Einschulung</h2></td><td width="50%"><h2>Abschluss</h2></td>
			</tr>
			<tr>
				<td width="50%">{$einschulung}</td><td width="50%">Hier das Jahr vom Abschluss</td>
			</tr>
		</table>		
		</blockquote>
</td>
</tr>
</table>
{$footer}
</body>
</html>') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title' => 'abschluss2',
		'template' => $db->escape_string('HIER STEHT DER INHALT VOM TEMPLATE2') ,
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
	
}

//INSTALLIEREN VOM PLUGIN - liefert true zurück, wenn Plugin installiert. Sonst false
	function abschluss_is_installed()
	{
		global $db, $mybb;
		
		if ($db->table_exists("abschluss_schule"))
		{
			return true;
		}
		return false;
	}
	
//DEINSTALLIEREN VOM PLUGIN
	function abschluss_uninstall()
	{ 
		global $db;
		//Datenbank-Eintrag löschen
		if ($db->table_exists("abschluss_schule"))
		{
			$db->drop_table("abschluss_schule");
		}
		
		//Einstellungen deinstallieren:
		$db->query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name='abschluss'"); //Gruppe löschen
		$db->query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name='abschluss_schuldesc'"); //Einzel-Einstellung löschen
		
		rebuild_settings();
		
		//Templates löschen:
		$db->delete_query("templates", "title LIKE '%abschluss%'");
		
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
			$page->output_header($lang->abschluss_manage." - ".$lang->abschluss_overview);
			
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
			//Optionen
			$form_container->output_row_header($lang->abschluss_options, array('style' => 'text-align: center; width: 5%;'));
			
			//Alle bisherigen Einträge herbeiziehen und nach Schulname sortieren
			$query = $db->simple_select("abschluss_schule", "*", "",
		                ["order_by" => 'schulname', 'order_dir' => 'ASC']);
			
			while($abschluss_schule = $db->fetch_array($query)) {
				
				//Gestaltung der Übersichtsseite, Infos die angezeigt werden 
				//Schulname
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($abschluss_schule['schulname']).'</strong>');
				//Schulbeschreibung
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($abschluss_schule['schuldesc']).'</strong>');
				//Einschulalter
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($abschluss_schule['schulalter']).'</strong>');
				//Wie viele Jahre besucht man die Schule?
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($abschluss_schule['schuljahre']).'</strong>');
				//Ab welchem Monat wird man ein Jahr später eingeschult?
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($abschluss_schule['schulmonate']).'</strong>');
				//Wo befindet sich die Schule?
				$form_container->output_cell('<strong>'.htmlspecialchars_uni($abschluss_schule['schulstandort']).'</strong>');
				
				//Pop Up für Bearbeiten & Löschen
				$popup = new PopupMenu("abschluss_{$abschluss_schule['schulid']}", $lang->abschluss_options);
				$popup->add_item(
		                $lang->abschluss_edit,
		                "index.php?module=config-abschluss&amp;action=edit_entry&amp;schulid={$abschluss_schule['schulid']}"
		        );
		        $popup->add_item(
		                $lang->abschluss_delete,
		                "index.php?module=config-abschluss&amp;action=delete_entry&amp;stopid={$abschluss_schule['schulid']}"
		               ."&amp;my_post_key={$mybb->post_code}"
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

                // keine Fehler - dann einfügen
                if (empty($errors)) {
	
                    $new_entry = array(
                        "schulid" => (int)$mybb->input['schulid'],
                        "schulname" => $db->escape_string($mybb->input['schulname']),
                        "schuldesc" => $db->escape_string($mybb->input['schuldesc']),
						"schulalter" => $db->escape_string($mybb->input['schulalter']),
                        "schuljahre" => $db->escape_string($mybb->input['schuljahre']),
                        "schulmonate" => $db->escape_string($mybb->input['schulmonate']),
						"schulstandort" => $db->escape_string($mybb->input['schulstandort'])
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
                $page->output_header($lang->abschluss_manage." - ".$lang->abschluss_overview);

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
                    $lang->abschluss_form_name."<em>*</em>",
                    $lang->abschluss_form_name_desc,
                    $form->generate_text_box('schulname', $mybb->input['schulname'])
                );
               
                $text_editor = $form->generate_text_area('schuldesc', $mybb->input['schuldesc'], array(
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
                    $lang->abschluss_form_alter. "<em>*</em>",
                    $lang->abschluss_form_alter_desc,
                    $form->generate_text_box('schulalter', $mybb->input['schulalter'])
                );
			 
			 	$form_container->output_row(
                    $lang->abschluss_form_jahre. "<em>*</em>",
                    $lang->abschluss_form_jahre_desc,
                    $form->generate_text_box('schuljahre', $mybb->input['schuljahre'])
                );
 
			 	$form_container->output_row(
                    $lang->abschluss_form_monate. "<em>*</em>",
                    $lang->abschluss_form_monate_desc,
                    $form->generate_text_box('schulmonate', $mybb->input['schulmonate'])
                );	
			 
			 	$form_container->output_row(
                    $lang->abschluss_form_standort. "<em>*</em>",
                    $lang->abschluss_form_standort_desc,
                    $form->generate_text_box('schulstandort', $mybb->input['schulstandort'])
                );

                $form_container->end();
                $buttons[] = $form->generate_submit_button($lang->abschluss_send);
                $form->output_submit_wrapper($buttons);
                $form->end();
                $page->output_footer();
    
                exit;         
        }

        
        
        if ($mybb->input['action'] == "edit_entry") {
            if ($mybb->request_method == "post") {
            	
            	
                // Prüfen, ob erforderliche Felder nicht leer sind
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

                // No errors - insert the terms of use
                if (empty($errors)) {
                    $schulid = $mybb->get_input('schulid', MyBB::INPUT_INT);

					
                    $edited_entry = [
                        "schulid" => (int)$mybb->input['schulid'],
                        "schulname" => $db->escape_string($mybb->input['schulname']),
                        "schuldesc" => $db->escape_string($mybb->input['schuldesc']),
						"schulalter" => $db->escape_string($mybb->input['schulalter']),
                        "schuljahre" => $db->escape_string($mybb->input['schuljahre']),
                        "schulmonate" => $db->escape_string($mybb->input['schulmonate']),
						"schulstandort" => $db->escape_string($mybb->input['schulstandort'])
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
            $page->output_header($lang->abschluss_manage." - ".$lang->abschluss_overview);
            
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

            $text_editor = $form->generate_text_area('schuldesc', htmlspecialchars_uni($edit_entry['schuldesc']), array(
                'id' => 'schuldesc'. "<em>*</em>",
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
                } 
                
                else {
					
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


// ONLINE LOCATION
$plugins->add_hook("fetch_wol_activity_end", "abschluss_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "abschluss_online_location");

function abschuss_online_activity($user_activity) {
global $parameters, $user;

    $split_loc = explode(".php", $user_activity['location']);
    if($split_loc[0] == $user['location']) {
        $filename = '';
    } else {
        $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
    }
    
    switch ($filename) {
        case 'abschluss':
        if(!isset($parameters['action']))
        {
            $user_activity['activity'] = "abschluss";
        }
        break;
    }
      
return $user_activity;
}

function abschluss_online_location($plugin_array) {
global $mybb, $theme, $lang;

	if($plugin_array['user_activity']['activity'] == "abschluss") {
		$plugin_array['location_name'] = "Berechnet gerade seinen/ihren <a href=\"abschluss.php\">Abschluss</a>.";
	}

return $plugin_array;
}
