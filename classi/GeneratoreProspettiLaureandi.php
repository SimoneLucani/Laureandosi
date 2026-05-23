<?php
// Genere il PDF del prospetto di laurea usando FPDF
require_once __DIR__."/../lib/fpdf184/fpdf.php";
require_once 'CarrieraLaureando.php';
require_once 'CarrieraLaureandoInf.php';
require_once 'ConfigurazioneSistema.php';

class GeneratoreProspettiLaureandi
{
    public string $matricola;
    public CarrieraLaureando $carriera;
    private string $cdl;
    private string $dataLaurea;

    public function __construct(string $matricola, string $cdl, string $dataLaurea)
    {
        $this->matricola = $matricola;
        $this->cdl = $cdl;
        $this->dataLaurea = $dataLaurea;

        //controllo se Ing. Informatica
        if ($cdl == "T. Ing. Informatica") {
            $this->carriera = new CarrieraLaureandoInf($this->matricola, $this->cdl, $this->dataLaurea);
        } else {
            $this->carriera = new CarrieraLaureando($this->matricola, $this->cdl, $this->dataLaurea);
        }

        $_SESSION['carriere'][] = $this->carriera;

        //Creo il pdf, verifico che la cartella di testinazione esista, e salvo su disco il file ("F")
        $pdf = new FPDF();

        //Aggiunge carriera al pdf passato
        $this->costruisciPdf($pdf);

        if (!file_exists(__DIR__."/../prospetti/$this->cdl")) {
            mkdir(__DIR__."/../prospetti/$this->cdl");
        }
        $pdf->Output(__DIR__."/../prospetti/$this->cdl/".$this->matricola."_prospetto.pdf", "F");
    }

    public function costruisciPdf(FPDF $pdf): void
    {
        //flag usato per la gestione degli studenti di ingegneria informatica
        //setto il flag
        //se info settato, aggiungo una colonna in pi+ ("INF")
        $info = $this->cdl == "T. Ing. Informatica";

        $pdf->SetMargins(11, 8);
        $pdf->AddPage();
        $font = "Arial";
        $pdf->SetFont($font, "", 13);
        $pdf->Cell(0, 6, $this->cdl, 0, 1, "C");
        $pdf->Cell(0, 6, "CARRIERA E SIMULAZIONE DEL VOTO DI LAUREA", 0, 1, "C");

        //stampa dei dati anagrafici
        $pdf->Ln(3);
        $pdf->SetFontSize(9);
        $pdf->Rect($pdf->GetX(), $pdf->GetY(), $pdf->GetPageWidth() - 22, $info ? 33 : 27.5);

        //Ogni riga è una coppia di Cell
        //L'etichetta con larghezza fissa 45
        //valore fino al margine (w=0) e va a capo (ln=1)
        $pdf->Cell(45, 5.5, "Matricola:", 0, 0);
        $pdf->Cell(0, 5.5, $this->matricola, 0, 1);
        $pdf->Cell(45, 5.5, "Nome:", 0, 0);
        $pdf->Cell(0, 5.5, $this->carriera->nome, 0, 1);
        $pdf->Cell(45, 5.5, "Cognome:", 0, 0);
        $pdf->Cell(0, 5.5, $this->carriera->cognome, 0, 1);
        $pdf->Cell(45, 5.5, "Email:", 0, 0);
        $pdf->Cell(0, 5.5, $this->carriera->email, 0, 1);
        $pdf->Cell(45, 5.5, "Data:", 0, 0);
        $pdf->Cell(0, 5.5, $this->dataLaurea, 0, 1);

        //stampa del bonus
        if ($info) {
            $pdf->Cell(45, 5.5, "Bonus:", 0, 0);
            $pdf->Cell(0, 5.5, $this->carriera->bonus ? "SI" : "NO", 0, 1);
        }
        $pdf->Ln(3);

        //stampa della tabella degli esami
        $pdf->Cell($pdf->GetPageWidth() - 22 - ($info ? 44 : 33), 5.5, "ESAME", 1, 0, "C");
        $pdf->Cell(11, 5.5, "CFU", 1, 0, "C");
        $pdf->Cell(11, 5.5, "VOT", 1, 0, "C");
        $pdf->Cell(11, 5.5, "MED", 1, 0, "C");
        //aggiunta della colonna relativa agli esami informatici
        if ($info) {
            $pdf->Cell(11, 5.5, "INF", 1, 0, "C");
        }
        $pdf->Ln();
        $pdf->SetFontSize(8);
        foreach ($this->carriera->esami as $esame) {
            $pdf->Cell($pdf->GetPageWidth() - 22 - ($info ? 44 : 33), 4.5, $esame->nome, 1, 0);
            $pdf->Cell(11, 4.5, $esame->cfu, 1, 0, "C");
            $pdf->Cell(11, 4.5, $esame->voto, 1, 0, "C");
            $pdf->Cell(11, 4.5, $esame->media ? "X" : "", 1, 0, "C");
            if ($info) {
                $pdf->Cell(11, 4.5, $esame->inf ? "X" : "", 1, 0, "C");
            }
            $pdf->Ln();
        }
        $pdf->Ln(3);

        //stampa dei dati di carriera
        $pdf->SetFontSize(9);
        $pdf->Rect($pdf->GetX(), $pdf->GetY(), $pdf->GetPageWidth() - 22, $info ? 33 : 22);
        $pdf->Cell(80, 5.5, "Media Pesata (M):", 0, 0);
        $pdf->Cell(0, 5.5, round($this->carriera->mediaPesata, 3), 0, 1);
        $pdf->Cell(80, 5.5, "Crediti che fanno media (CFU):", 0, 0);
        $pdf->Cell(0, 5.5, $this->carriera->cfuMedia, 0, 1);
        $pdf->Cell(80, 5.5, "Crediti curriculari conseguiti:", 0, 0);
        $pdf->Cell(
            0,
            5.5,
            $this->carriera->calcolaCfuCurricolari()."/".ConfigurazioneSistema::getCfuCurricolari($this->cdl),
            0,
            1
        );
        if ($info) {
            $pdf->Cell(80, 5.5, "Voto di tesi (T):", 0, 0);
            $pdf->Cell(0, 5.5, 0, 0, 1);
        }
        $pdf->Cell(80, 5.5, "Formula calcolo voto di laurea:", 0, 0);
        $pdf->Cell(0, 5.5, ConfigurazioneSistema::getFormulaVoto($this->cdl), 0, 1);
        //stampa media degli esami informatici
        if ($info) {
            $pdf->Cell(80, 5.5, "Media pesata esami INF:", 0, 0);
            $pdf->Cell(0, 5.5, round($this->carriera->mediaInformatica, 3), 0, 1);
        }
    }



    //aggiunge la simulazione al prospetto passato
    //Viene chiamata da Commissione, non da Laureandi
    // implemento qui perché ho this->carriera pronto all'uso

    //Se ci sono troppi step(es. la commissione può dare da 18 a 33 alla tesi),
    // divido la tabella in due colonne affiancate per non far finire il pdf nella pagina successiva
    // ceil($nCell/2) approssima per eccesso all'intero più vicino
    public function aggiungiTabella(FPDF $pdf): void
    {
        $pdf->Ln(3);
        $pdf->SetFontSize(9);
        $pdf->Cell(($pdf->GetPageWidth() - 22), 5.5, "SIMULAZIONE DI VOTO DI LAUREA", 1, 1, "C");

        //gestione della formula
        //ricevo "4*M*CFU*T*18)
        //sostistuisco "CFU" con "A"
        //sostituisco M, T, A,C con $M, $C...
        $formulaVoto = ConfigurazioneSistema::getFormulaVoto($this->cdl);
        $formulaVoto = str_replace('CFU', "A", $formulaVoto);
        $formulaVoto = str_replace(["M", 'T', 'A', 'C'], ['$M', '$T', '$A', '$C'], $formulaVoto);


        //praram indica quale variabile va simulata (Es. Informatica è C)
        // ["param => "C", "min" => 1, "max" => 7, "step" => 1]
        $param = ConfigurazioneSistema::getInfoParametro($this->cdl);
        //calcolo il numero di celle della simulazione in base all'intervallo e allo step del parametro
        $nCell = (int)(($param["max"] - $param["min"]) / $param["step"] + 1);


        $M = $this->carriera->mediaPesata;
        $A = ConfigurazioneSistema::getCfuCurricolari($this->cdl);
        $C = 0;
        $T = 0;
        $voto = 0;

        //simulazioni: meno di 10 celle
        if ($nCell <= 10) {
            $pdf->Cell(
                ($pdf->GetPageWidth() - 22) / 2,
                5.5,
                $param["param"] == "T" ? "VOTO TESI" : "VOTO COMMISSIONE",
                1,
                0,
                "C"
            );
            $pdf->Cell(($pdf->GetPageWidth() - 22) / 2, 5.5, "VOTO DI LAUREA", 1, 1, "C");
            for ($i = $param["min"]; $i <= $param["max"]; $i += $param["step"]) {
                if ($param["param"] == "T") {
                    $T = $i;
                } else {
                    $C = $i;
                }
                //eval prende una stringa e la esegue come se fosse codice
                // così da avere flessibilità assoluta nelle formule
                // altrimenti dovrei usare serie di if o switch per ogni formula (poco scalabile)
                eval("\$voto = $formulaVoto;");

                $pdf->Cell(($pdf->GetPageWidth() - 22) / 2, 5.5, $i, 1, 0, "C");
                $pdf->Cell(($pdf->GetPageWidth() - 22) / 2, 5.5, round($voto, 3), 1, 1, "C");
            }
        } else { //simulazioni con oltre 10 celle
            $pdf->Cell(
                ($pdf->GetPageWidth() - 22) / 4,
                5.5,
                $param["param"] == "T" ? "VOTO TESI" : "VOTO COMMISSIONE",
                1,
                0,
                "C"
            );
            $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 5.5, "VOTO DI LAUREA", 1, 0, "C");
            $pdf->Cell(
                ($pdf->GetPageWidth() - 22) / 4,
                5.5,
                $param["param"] == "T" ? "VOTO TESI" : "VOTO COMMISSIONE",
                1,
                0,
                "C"
            );
            $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 5.5, "VOTO DI LAUREA", 1, 1, "C");
            $even = 0;
            for ($i = 0; $i < $nCell; $i++) {
                //colonna sinistra
                if ($even == 0) {
                    $val = $param["min"] + $param["step"] * ($i / 2);
                } //colonna destra, si aggiunge una costante
                else {
                    $val = $param["min"] + $param["step"] * (ceil($nCell / 2) + ($i - 1) / 2);
                } //ceil arrotonda in eccesso
                if ($param["param"] == "T") {
                    $T = $val;
                } else {
                    $C = $val;
                }
                eval("\$voto = $formulaVoto;"); //eval() esegue codice PHP: se formulaVoto fosse $T + 2 ==> $voto = $T + 2;
                $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 5.5, $val, 1, 0, "C");
                $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 5.5, round($voto, 3), 1, $even || ($i == $nCell - 1), "C");
                $even = $even == 0 ? 1 : 0;
            }
        }
        $pdf->Ln(3);
        $pdf->MultiCell(0, 4, "VOTO DI LAUREA FINALE: ".ConfigurazioneSistema::getMessaggioProspetto($this->cdl));
    }
}