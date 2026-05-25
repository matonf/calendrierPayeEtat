<?php
define('CACHE_TTL', 9000);
$cacheFile = 'cache-paye.ics';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TTL)
{
    // Serve cached content
    readfile($cacheFile);
    exit;
}

// Commencez à capturer la sortie
ob_start();

//coupe les phrases en 75 caractères max, ajoute les CRLF+espace en fin de ligne, protège certains caractères en respect de la RFC : ,;
function split75($ch)
{
    return wordwrap(addcslashes($ch, ",;"), 75, " \r\n ");
}
//crée un événement de paye, en gérant les week-ends et jours fériés
function ajouterPaye($mois, $uid, $summary, $desc="", $rapide=false,)
{
    //gestion spéciale de Noël
    if ($mois == 12) $jour = mktime(0, 0, 0, $mois, 24);
    //sinon le dernier du mois
    else $jour = mktime(0, 0, 0, ($mois+1), 0);
    //dimanche : -2
    if (date("N", $jour)  == 7) $jour -= (2*86400);
    //samedi : -1
    if (date("N", $jour)  == 6) $jour -= 86400;
    $jour = $jour-(2*86400);
    if (date("N", $jour)  == 6 || date("N", $jour)  == 7) $jour -= (2*86400);
    if ($rapide)
    {
        //réception en avance d'un jour chez Boursobank et autres banques
        $jour -= 86400;
        if (date("N", $jour)  == 6 || date("N", $jour)  == 7) $jour -= (2*86400);
    }
    //formatage de la date
    $date = date('Ymd', $jour);
    return "BEGIN:VEVENT" . "\r\n" . "DTSTAMP:" . $date. "\r\n" . "UID:$uid" . "\r\n" . "DTSTART:" . $date . "\r\n" .  "SUMMARY:$summary" . "\r\n" . split75("DESCRIPTION:$desc") . "\r\n" . "END:VEVENT" . "\r\n";
}


//entête du calendrier
$cal = "BEGIN:VCALENDAR" . "\r\n" . "VERSION:2.0" . "\r\n" . "PRODID:-//CAL PAYE//FONC//FR" . "\r\n" . "X-WR-CALNAME;LANGUAGE=fr:💶 Calendrier de la paye" . "\r\n" . "METHOD:PUBLISH" . "\r\n";

//choisissez entre l'année glissante, du mois courant à +12
//for ($i=0; $i<12; $i++) $cal .= ajouterEven(date("n")+$i, "PAYE-$i", "💶 Jour de paye !");
//ou l'année civile
for ($i=1; $i<=12; $i++) $cal .= ajouterPaye($i, "PAYE-$i", "💶 Jour de paye !", "Le code source est disponible ici : https://github.com/matonf/calendrierPayeEtat. Le calendrier est à cette adresse : https://onfray.info/cache-paye.ics");
//fin du calendrier
$cal .= "END:VCALENDAR";
echo $cal;

// Récupère la sortie générée
$content = ob_get_clean();

// Enregistre le contenu dans le cache
file_put_contents($cacheFile, $content);

// Affiche le contenu généré
echo $content;
?>
