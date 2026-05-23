<?php
// Utilizzata da API.php
require_once "GeneratoreProspettiCommissione.php";
require_once "InvioProspetti.php";
require_once "GestioneCarrieraStudente.php";

class InterfacciaUnitaDidattica
{
    private array $matricole;
    private string $cdl;
    private string $dataLaurea;

    public function __construct(string $matricole, string $cdl, string $dataLaurea)
    {
        $this->matricole = explode(" ", $matricole); //divido sullo spazio
        $this->cdl = $cdl;
        $this->dataLaurea = $dataLaurea;
    }

    // Controlla che le matricole inserite siano corrette e che appartengano allo stesso CdL
    public function controlloMatricole(): bool
    {
        $cdl = null;
        foreach ($this->matricole as $m) {
            $esami = GestioneCarrieraStudente::prelevaCarriera($m);
            //controllo l'esistenza della matricola controllando se è presente la sua carriera
            if ($esami && is_array($esami)) {
                if ($cdl == null) //si prende il corso dal primo esame
                {
                    $cdl = $esami[0]['CORSO']; //prendo il cdl
                } else {
                    if ($cdl != $esami[0]['CORSO']) //verifica che gli studenti appartengano allo stesso cdl
                    {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public function generaProspetti(): void
    {
        $_SESSION['carriere'] = [];
        //delega interamente a GeneratoreProspettiCommissione
        new GeneratoreProspettiCommissione($this->matricole, $this->cdl, $this->dataLaurea);
    }


    public function generaLink(): string
    {
        return str_replace(
            " ",
            "%20", //pulizia, riscrivo lo spazio vuoto come il suo codice ASCII
            site_url("/wp-content/themes/codice/prospetti/$this->cdl/commissione_prospetto.pdf")
            //site_url funzione wordpress che serve a generare URL completo
            //nella cartella prospetti, prenderà quella del corso selezionato
        );
    }

    public function inviaProspetti(): void
    {
        new InvioProspetti($this->cdl);
    }
}