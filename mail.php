<?php
/**
 * Rancho Cascada – Kontaktformular-Mailer
 * Einfach die E-Mail-Adresse unten anpassen, fertig.
 */

$empfaenger = 'Lamamamamarion@gmx.de'; // <-- Eure E-Mail-Adresse hier eintragen

// Nur POST-Anfragen akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// Eingaben bereinigen
function clean($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

$vorname   = clean($_POST['vorname']   ?? '');
$nachname  = clean($_POST['nachname']  ?? '');
$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$telefon   = clean($_POST['telefon']   ?? '');
$betreff   = clean($_POST['betreff']   ?? 'Anfrage');
$nachricht = clean($_POST['nachricht'] ?? '');

// ── Spam-Schutz ──────────────────────────────────────────────────────
// Bots werden stillschweigend auf "ok" umgeleitet, damit sie nicht merken,
// dass ihre Nachricht verworfen wurde.
function spam_exit() {
    header('Location: index.html?status=ok');
    exit;
}

// 1) Honeypot: unsichtbares Feld "firma" – Menschen sehen es nicht, Bots füllen es aus
if (!empty($_POST['firma'])) {
    spam_exit();
}

// 2) Zeitprüfung: Formular muss mindestens 3 Sekunden offen gewesen sein
$ts = (int)($_POST['ts'] ?? 0);
if ($ts <= 0 || (time() - $ts) < 3) {
    spam_exit();
}

// 3) Kyrillische Zeichen: kommen bei echten Anfragen praktisch nie vor
if (preg_match('/\p{Cyrillic}/u', $vorname . $nachname . $nachricht)) {
    spam_exit();
}

// 4) Links in der Nachricht: typisch für Spam (echte Gäste schicken selten URLs)
if (preg_match('/https?:\/\/|www\./i', $nachricht) && substr_count(strtolower($nachricht), 'http') > 1) {
    spam_exit();
}

// Pflichtfelder prüfen
if (empty($vorname) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?status=fehler');
    exit;
}

// E-Mail zusammenbauen
$subject = "Neue Anfrage: $betreff – von $vorname $nachname";

$body  = "Neue Nachricht über das Kontaktformular auf alpakahof-neuenhagen.com\n\n";
$body .= "Name:     $vorname $nachname\n";
$body .= "E-Mail:   $email\n";
$body .= "Telefon:  " . ($telefon !== '' ? $telefon : '(nicht angegeben)') . "\n";
$body .= "Betreff:  $betreff\n\n";
$body .= "Nachricht:\n$nachricht\n";

$headers  = "From: Rancho Cascada <noreply@alpakahof-neuenhagen.com>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Senden
$sent = mail($empfaenger, $subject, $body, $headers);

// Weiterleitung mit Status
header('Location: index.html?status=' . ($sent ? 'ok' : 'fehler'));
exit;
