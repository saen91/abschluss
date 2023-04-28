
<?php
define("IN_MYBB", 1);
require("global.php");

//Settings 
// $gleiches_jahr ja / nein(ja)
// $Stichmonat_Einschulung(September)
// $Stichmonat_Ende: (Juli)
// $Schuljahre_Dauer: (7 Jahre)
// $Einschulung_alter(standard. - 11 Jahre)

// eingabe: geburtstag des Charakters  4.9.2006

//hard füllen der variablen

//liegen start monat und abschluss im gleichen jahr? 0 -> nein 1 ->ja
$gleiches_jahr = 0; // also z.b. Januar anfang dezember ende
//monat anfang schuljahr
$stichmonat_einschulung = 9;

//monat ende schuljahr
$stichmonat_ende = 7;
//wie alt während der einschulung
$einschulung_alter = 7;
//wieviele schuljahre
$schuljahre_dauer = 11;

//Schülerinfos
$geburtsmonat = 7;
$geburtsjahr = 2006;

//häßliches echos
echo "gleiches jahr: $gleiches_jahr (0 -> abschluss monat im gleichen jahr - 1 nicht)
<br> stichmonat_einschulung = $stichmonat_einschulung
<br> stichmonat_ende = $stichmonat_ende
<br> einschulung_alter = $einschulung_alter
<br> schuljahre_dauer = $schuljahre_dauer 
<br><br> geburtsmonat = $geburtsmonat 
<br> geburtsjahr  = $geburtsjahr <br><br>
";

//wir berechnen die einschulung
echo "berechnung einschulung: <br>
  hai if gmonat $geburtsmonat >= einschulungmonat $stichmonat_einschulung<br> ";
if ($geburtsmonat >= $stichmonat_einschulung) {
  $rechnung = $geburtsjahr + $einschulung_alter + 1;
  echo " $rechnung = $geburtsjahr + $einschulung_alter + 1;";
} else {
  //geburtstag vor stichmonat
  $rechnung = $geburtsjahr + $einschulung_alter;
  echo "  else <br>  $rechnung = $geburtsjahr + $einschulung_alter;";
}

//verbinden mit monat zu string
$einschulung = $stichmonat_einschulung . " - " . $rechnung;

//Berechnen des Abschlussjahrs:
$abschlussjahr = $rechnung + $schuljahre_dauer;
echo "<br><br>Berechnen des Abschlussjahrs: <br>
$abschlussjahr = $rechnung(einschulung) + $schuljahre_dauer (dauer); ";

if ($gleiches_jahr == 1 ) {
  echo "<br>if (gleiches jahr $gleiches_jahr == 1 ) ";
  $abschlussjahr = $abschlussjahr - 1;
  echo "$abschlussjahr = $abschlussjahr - 1;";
}

// String:
$abschluss = $stichmonat_ende . " - " . $abschlussjahr;

echo "<h2>einschulung monat $einschulung</h2>";
echo "<h2>abschluss monat $abschluss</h2>";
