<?php

require_once "classi/InterfacciaUnitaDidattica.php";

// Avvia o riprende la sessione
session_start();

// Reset variabili di sessione
$_SESSION["stato"] = "";
$_SESSION["link"] = "";
$_SESSION["statoErrori"] = "";
$_SESSION["disabilitato"] = "disabled";
$_SESSION["modalita"] = "";

// Gestione del test
//se all'URL aggiungo ?test si attiva la modalità test
if (isset($_GET["test"])) {
    require_once "test.php";
    $_SESSION["modalita"] = "class=\"modalita-test\"";
    $test = new Test();
}

// Se invece non sono in fase di test, ma in uso normale:


// CREAZIONE DEI PROSPETTI
// Il post deve contenere tutti e 4 i parametri contemporaneamente,
// quindi deve essere stato cliccato crea ("name" del bottone Crea Prospetti), e inseriti cdl,data e matricole.
if (isset($_POST["cdl"], $_POST["data"], $_POST["matricole"], $_POST["crea"])) {

    // Crea oggetto IntefacciaUnitaDidattica con i dati inseriti.
    // Salvo tutto nella sessione a datiSessione, per mantenere sempre salvati i dati
    $_SESSION["datiSessione"] = new InterfacciaUnitaDidattica($_POST["matricole"], $_POST["cdl"], $_POST["data"]);

    // Controllo che le matricole inserite esistano e che appartengano allo stesso CdL
    if (!$_SESSION["datiSessione"]->controlloMatricole()) {
        $_SESSION["statoErrori"] = "Una o più matricole errate!";

    // Se no errori,
    } else {
        $_SESSION["datiSessione"]->generaProspetti(); //genero prospetti
        $_SESSION["link"] = $_SESSION["datiSessione"]->generaLink(); //salvo URL in SESSION["link"] per la fase di revisione
        $_SESSION["stato"] = "Prospetti creati";
        $_SESSION["disabilitato"] = ""; // Abilito pulsante invio
    }
}

// Invio prospetti
// Si attiva quando viene premuto il pulsante invio
if (isset($_POST["invia"])) {
    // Riutilizzo oggetto InterfacciaUnitaDidattica salvato in SESSION["datiSessione"]
    $_SESSION["datiSessione"]->inviaProspetti();
    // Risalvo il link, perché al refresh viene resettato
    $_SESSION["link"] = $_SESSION["datiSessione"]->generaLink();
}