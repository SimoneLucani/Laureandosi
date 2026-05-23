<?php
// Utilizzata FPDF per la sua flessibilità nella gestione delle celle
// e perché complessità di MPDF (come conversione da HTML) non necessaria
require_once __DIR__."/../lib/fpdf184/fpdf.php";
require_once 'GeneratoreProspettiLaureandi.php';

class GeneratoreProspettiCommissione
{
    private array $matricole;
    private array $prospettiLaureandi;
    private string $cdl;
    private string $dataLaurea;

    // Memorizza i dati ricevuti e chiama genera()
    public function __construct(array $matricole, string $cdl, string $dataLaurea)
    {
        $this->matricole = $matricole;
        $this->cdl = $cdl;
        $this->dataLaurea = $dataLaurea;

        $this->prospettiLaureandi = [];

        $this->genera();
    }

    //itera su ogni matricola dell'array, per ognuna crea un oggetto GPL e lo aggiunge in fondo all'array
    private function genera(): void
    {
        foreach ($this->matricole as $m) {
            $prospettoLaureando = new GeneratoreProspettiLaureandi($m, $this->cdl, $this->dataLaurea);
            $this->prospettiLaureandi[] = $prospettoLaureando;
        }

        // Creazione del pdf
        $pdf = new FPDF();
        $pdf->SetMargins(11, 8);
        $pdf->AddPage();
        $font = "Arial";

        // Intestazione della tabella riassuntiva
        $pdf->SetFont($font, "", 13);
        $pdf->Cell(0, 6, $this->cdl, 0, 1, "C");
        $pdf->Cell(0, 6, "LISTA LAUREANDI", 0, 1, "C");
        $pdf->Ln(3);
        $pdf->SetFontSize(11);

        //Intestazioni tabella
        // 4 colonne dividendo per 4 lo spazio della pagina meno i margini (11mm per lato)
        $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 7, "COGNOME", 1, 0, "C");
        $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 7, "NOME", 1, 0, "C");
        $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 7, "CDL", 1, 0, "C");
        $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 7, "VOTO LAUREA", 1, 1, "C");

        //Riempo la tabella iniziale
        // Ogni laureando una riga
        $pdf->SetFontSize(10);
        foreach ($this->prospettiLaureandi as $prospetto) {
            $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 6, $prospetto->carriera->cognome, 1, 0, "C");
            $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 6, $prospetto->carriera->nome, 1, 0, "C");
            $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 6, "", 1, 0, "C");
            $pdf->Cell(($pdf->GetPageWidth() - 22) / 4, 6, "/110", 1, 1, "C");
        }

        // Per ogni laureando aggiungo il pdf e la tabella di simulazione
        // delegando a GeneratoreProspettiLaureandi
        foreach ($this->prospettiLaureandi as $prospetto) {
            $prospetto->costruisciPdf($pdf);
            $prospetto->aggiungiTabella($pdf);
        }

        // Salva il pdf ("F"  sta per file)
        //la cartella del cdl è stata sicuramente già creata durante la generazione del prospetto del primo laureando
        $pdf->Output(__DIR__."/../prospetti/$this->cdl/commissione_prospetto.pdf", "F");
    }
}