<?php
/**
 * Rancho Cascada – Kontaktformular-Mailer
 * Einfach die E-Mail-Adresse unten anpassen, fertig.
 */

$empfaenger = 'info@rancho-cascada.de'; // <-- Eure E-Mail-Adresse hier eintragen

// Nur POST-Anfragen akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Eingaben bereinigen
function clean($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

$vorname  = clean($_POST['vorname']  ?? '');
$nachname = clean($_POST['nachname'] ?? '');
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$betreff  = clean($_POST['betreff']  ?? 'Anfrage');
$nachricht = clean($_POST['nachricht'] ?? '');

// Pflichtfelder prüfen
if (empty($vorname) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?status=fehler');
    exit;
}

// E-Mail zusammenbauen
$subject = "Neue Anfrage: $betreff – von $vorname $nachname";

$body  = "Neue Nachricht über das Kontaktformular auf rancho-cascada.de\n\n";
$body .= "Name:     $vorname $nachname\n";
$body .= "E-Mail:   $email\n";
$body .= "Betreff:  $betreff\n\n";
$body .= "Nachricht:\n$nachricht\n";

$headers  = "From: Rancho Cascada Website <noreply@rancho-cascada.de>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Senden
$sent = mail($empfaenger, $subject, $body, $headers);

// Weiterleitung mit Status
header('Location: index.html?status=' . ($sent ? 'ok' : 'fehler'));
exit;
