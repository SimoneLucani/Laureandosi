<?php

use PHPMailer\PHPMailer\PHPMailer;

require __DIR__."/../lib/PHPMailer/src/PHPMailer.php";
require __DIR__."/../lib/PHPMailer/src/SMTP.php";
require __DIR__."/../lib/PHPMailer/src/Exception.php";
require_once "CarrieraLaureando.php";
require_once "ConfigurazioneSistema.php";

class InvioProspetti
{
    public function __construct(string $cdl)
    {
        //recupera le carriere dalla sessione
        // non ricalcola tutto, lo trova già
        $carriere = $_SESSION['carriere'];
        $totali = count($carriere);
        $inviate = 0;
        $_SESSION["stato"] = "Prospetti inviati: ".$inviate."/".$totali;

        $email = new PHPMailer();
        $email->IsSMTP();
        $email->Host = "mixer.unipi.it"; // server unipi
        $email->SMTPSecure = "";
        $email->Port = 25; // porta std
        $email->SMTPAuth = false; // no autenticazione

        $email->addCustomHeader('Content-Type', 'text/plain; windows-1252');//conversione da utf-8 a win-1252(latin-1) (per à,è,ì...)
        $email->setFrom("no-reply-laureandosi@ing.unipi.it", "Laureandosi 2.0");
        $email->Subject = "Appello di laurea in ".$cdl."- indicatori per voto di laurea";
        $email->Body = mb_convert_encoding(ConfigurazioneSistema::getEmailBody($cdl), 'Windows-1252', 'UTF-8');

        foreach ($carriere as $destinatario) {
            $email->clearAddresses(); //rimuovo dest precedente
            $email->clearAttachments(); //rimuovo allegato precedente
            $email->AddAddress($destinatario->email); // nuovo destinatario
            $email->addAttachment(__DIR__."/../prospetti/$cdl/".$destinatario->matricola."_prospetto.pdf"); //allegato specifico

            if (!$email->Send()) {
                error_log("PHPMailer error: " . $email->ErrorInfo);
                $_SESSION["statoErrori"] .= "Errore nell'invio delle email, riprovare  <br>";
                break;
            } else {
                //il numero di mail inviate viene aggiornato
                $inviate++;
                // Attendo 5 secondi tra un invio e l'altro
                if ($inviate < $totali) {
                    sleep(5); // aspetto solo se ci sono altri invii
                }
            }
        }

        $_SESSION["stato"] = "Prospetti inviati: ".$inviate."/".$totali;

        // Chiude la connessione SMTP
        $email->SmtpClose();
    }
}