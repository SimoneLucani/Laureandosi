<?php

require_once __DIR__."/classi/CarrieraLaureando.php";
require_once __DIR__."/classi/CarrieraLaureandoInf.php";
require_once __DIR__."/classi/GestioneCarrieraStudente.php";
require_once __DIR__."/classi/ConfigurazioneSistema.php";

class Test
{
    // Contatori globali per il riepilogo finale
    private int $totaleTest = 0;
    private int $testPassati = 0;
    private array $datiTest;


    // Quando viene creato avvia subito i test e poi stampa il riepologo
    public function __construct()
    {
        //Carica e decodifica il JSON una volta all'avvio
        $json = file_get_contents(__DIR__."/config/test_casi.json");
        $this->datiTest = json_decode($json, true) ?? [];

        $this->eseguiTest("ANAGRAFICA", fn() => $this->testAnagrafica());
        $this->eseguiTest("BONUS", fn() => $this->testBonus());
        $this->eseguiTest("CORSI DI LAUREA", fn() => $this->testCdl());
        $this->eseguiTest("ESAMI", fn() => $this->testEsami());
        $this->eseguiTest("CALCOLI", fn() => $this->testCalcoli());
        $this->stampaRiepilogo();
    }

    private function eseguiTest(string $nome, callable $test): void
    {
        $_SESSION["statoErrori"] .= "<br><b>&#9654; TEST: $nome</b><br>"; // Intestazione singolo test
        $erroriPrima = substr_count($_SESSION["statoErrori"], "FAIL"); // Conta in $SESSION["stato_errori"] quante volte è presente "FAIL"
        $test(); // esegue il test passato come parametro
        $erroriDopo = substr_count($_SESSION["statoErrori"], "FAIL");
        if ($erroriDopo === $erroriPrima) {
            $_SESSION["statoErrori"] .= "<span style='color:green'>&#10003; Test completato senza errori.</span><br>";
        }
    }

    //
    private function check(string $contesto, mixed $atteso, mixed $ottenuto): bool
    {
        $this->totaleTest++;
        if ($atteso == $ottenuto) {
            $this->testPassati++;
            return true;
        }
        // Se fallisce aggiunge una riga rossa con FAIL
        $_SESSION["statoErrori"] .= "<span style='color:red'>FAIL [$contesto] atteso: \"$atteso\" &mdash; ottenuto: \"$ottenuto\"</span><br>";
        return false;
    }

    private function stampaRiepilogo(): void
    {
        $colore = $this->testPassati === $this->totaleTest ? "green" : "red";
        $_SESSION["statoErrori"] .=
            "<br><b>RIEPILOGO: <span style='color:$colore'>"
            .$this->testPassati."/".$this->totaleTest." test passati</span></b><br>";
    }


    // -------------------------------------------------------------------------
    // TEST ANAGRAFICA
    // Verifica che CarrieraLaureando recuperi correttamente nome, cognome,
    // matricola, dataLaurea e dataIscrizione per le matricole di riferimento
    // -------------------------------------------------------------------------
    private function testAnagrafica(): void
    {
        //Atteso
        $casi = $this->datiTest['anagrafica'] ?? [];

        foreach ($casi as [$matricola, $cdl, $dataLaurea, $nomeAtteso, $cognomeAtteso, $iscrizioneAttesa]) {
            $c = new CarrieraLaureando($matricola, $cdl, $dataLaurea);
            $prefisso = "Matricola $matricola";
            $this->check("$prefisso — matricola",       $matricola,                  $c->matricola);
            $this->check("$prefisso — nome",            $nomeAtteso,                 $c->nome);
            $this->check("$prefisso — cognome",         $cognomeAtteso,              $c->cognome);
            $this->check("$prefisso — dataLaurea",      strtotime($dataLaurea),      $c->dataLaurea);
            $this->check("$prefisso — dataIscrizione",  strtotime($iscrizioneAttesa),$c->dataIscrizione);
        }
    }


    // -------------------------------------------------------------------------
    // TEST BONUS
    // Verifica che CarrieraLaureandoInf calcoli correttamente il diritto al bonus
    // Il bonus spetta se la laurea cade entro 3.6 anni dall'iscrizione
    // -------------------------------------------------------------------------
    private function testBonus(): void
    {
        $casi = $this->datiTest['bonus'] ?? [];

        foreach ($casi as [$matricola, $dataLaurea, $bonusAtteso]) {
            $c = new CarrieraLaureandoInf($matricola, "T. Ing. Informatica", $dataLaurea);
            $this->check(
                "Bonus matricola $matricola",
                $bonusAtteso ? "true" : "false",
                $c->bonus ? "true" : "false"
            );
        }
    }


    // -------------------------------------------------------------------------
    // TEST CdL
    // Verifica che tutti i corsi di laurea supportati siano presenti in cdl.json
    // -------------------------------------------------------------------------
    private function testCdl(): void
    {
        $cdlSupportati = $this->datiTest['cdl_supportati'] ?? [];

        $json = file_get_contents(__DIR__."/config/cdl.json");
        $cdlJson = json_decode($json, true) ?? [];

        foreach ($cdlSupportati as $cdl) {
            $presente = array_key_exists($cdl, $cdlJson) ? "presente" : "assente";
            $this->check("CdL in cdl.json — $cdl", "presente", $presente);
        }

        // Verifica che i campi obbligatori esistano per ogni CdL
        $campiObbligatori = ["formula", "crediti_totali", "valore_lode", "info_parametro", "corpo_email"];
        foreach ($cdlSupportati as $cdl) {
            if (!isset($cdlJson[$cdl])) continue;
            foreach ($campiObbligatori as $campo) {
                $hasCampo = array_key_exists($campo, $cdlJson[$cdl]) ? "presente" : "assente";
                $this->check("Campo \"$campo\" per $cdl", "presente", $hasCampo);
            }
        }
    }


    // -------------------------------------------------------------------------
    // TEST ESAMI
    // Verifica il numero di esami filtrati e che nessun esame sia stato perso
    // -------------------------------------------------------------------------
    private function testEsami(): void
    {
        $casi = $this->datiTest['esami'] ?? [];

        foreach ($casi as [$matricola, $cdl, $dataLaurea, $nEsamiAttesi]) {
            $c = new CarrieraLaureando($matricola, $cdl, $dataLaurea);

            // Verifica conteggio
            $this->check(
                "N. esami matricola $matricola",
                $nEsamiAttesi,
                count($c->esami)
            );

            // Verifica completezza: nessun esame della carriera deve essere stato perso
            $esamiGrezzi    = GestioneCarrieraStudente::prelevaCarriera($matricola);
            $daRimuovere    = ConfigurazioneSistema::getEsamiNonCarriera($cdl, $matricola);
            $nomiNelProspetto = array_map(fn($e) => $e->nome, $c->esami);

            foreach ($esamiGrezzi as $esameGrezzo) {
                if (is_array($esameGrezzo["DES"]))          continue; // DES multiplo, skip
                if ($esameGrezzo["SOVRAN_FLG"] == 1)        continue; // sovrannumerario, skip
                if (in_array($esameGrezzo["DES"], $daRimuovere)) continue; // filtrato per config, skip

                $presente = in_array($esameGrezzo["DES"], $nomiNelProspetto) ? "presente" : "assente";
                $this->check(
                    "Esame \"{$esameGrezzo['DES']}\" in prospetto $matricola",
                    "presente",
                    $presente
                );
            }
        }
    }


    // -------------------------------------------------------------------------
    // TEST CALCOLI
    // Verifica media pesata, CFU media, CFU curricolari, CFU corso, media INF
    // -------------------------------------------------------------------------
    private function testCalcoli(): void
    {
        $casi = $this->datiTest['calcoli'] ?? [];

        foreach ($casi as [$matricola, $cdl, $dataLaurea, $mediaAtt, $cfuMediaAtt, $cfuCurrAtt, $cfuCorsoAtt, $mediaInfAtt]) {
            $c = $cdl === "T. Ing. Informatica"
                ? new CarrieraLaureandoInf($matricola, $cdl, $dataLaurea)
                : new CarrieraLaureando($matricola, $cdl, $dataLaurea);

            $this->check("Media pesata $matricola",         $mediaAtt,    round($c->mediaPesata, 3));
            $this->check("CFU media $matricola",            $cfuMediaAtt, $c->cfuMedia);
            $this->check("CFU curricolari $matricola",      $cfuCurrAtt,  $c->calcolaCfuCurricolari());
            $this->check("CFU corso $cdl",                  $cfuCorsoAtt, ConfigurazioneSistema::getCfuCurricolari($cdl));

            if ($mediaInfAtt !== null) {
                $this->check("Media INF $matricola", $mediaInfAtt, round($c->mediaInformatica, 3));
            }
        }
    }
}