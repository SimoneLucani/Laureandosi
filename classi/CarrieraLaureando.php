<?php
// Rappresenta la carriera completa di un laureando
// Recupera e processa tutti i dati necessari per il prospetto
require_once 'GestioneCarrieraStudente.php';
require_once 'ConfigurazioneSistema.php';
require_once 'Esame.php';

class CarrieraLaureando
{
    public string $nome;
    public string $cognome;
    public string $matricola;
    public string $email;
    public ?int $dataIscrizione = null; //nel caso lo studente sia di ingegneria informatica serve per la verifica del bonus
    public int $dataLaurea;
    public array $esami = []; //array contenente gli esami del laureando
    public int $cfuMedia; //somma dei cfu degli esami che concorrono alla media pesata
    public float $mediaPesata;

    public function __construct(string $matricola, string $cdl, string $dataLaurea)
    {
        $this->matricola = $matricola;
        $this->dataLaurea = strtotime($dataLaurea);

        // Recupero ee salvo i dati anagrifia e carriera da GestioneCarrieraStudente così li chiamo una sola volta
        $anagrafica = GestioneCarrieraStudente::prelevaAnagrafica($matricola);
        $esami = GestioneCarrieraStudente::prelevaCarriera($matricola);
        $this->nome = $anagrafica["nome"];
        $this->cognome = $anagrafica["cognome"];
        $this->email = $anagrafica["email_ate"];


        // Recupero esami da escludere
        // 1 chiamata a getEsamiNonCarriera e 1 a getEsamiNonMedia
        // legge da config/filtri.json la lista degli esami che non devono comparire nel prospetto e quelli che non fanno media
        $esamiNonCarriera = ConfigurazioneSistema::getEsamiNonCarriera($cdl, $matricola);
        $esamiNonMedia = ConfigurazioneSistema::getEsamiNonMedia($cdl, $matricola);

        // esami è l'array multilivello restituito da prelevaCarriera
        // per ogni indice, va creato un oggetto strutturato Esame
        foreach ($esami as $esame) {
            if ($this->dataIscrizione == null) //la data di iscrizione del laureando viene prelevata dal primo esame conseguito
            {
                $this->dataIscrizione = strtotime(str_replace("/", "-", $esame["INIZIO_CARRIERA"]));
            }
            //non vengono considerati gli esami sovrannumerari e gli esami che per altre motivazioni sono contenuti tra gli esami da rimuovere in filtri.json
            // se sovr, se il nome non è una stringa(corrotto),se peso non è un int, se da escludere per config.
            if ($esame["SOVRAN_FLG"] == 1 || !is_string($esame["DES"]) || !is_int($esame["PESO"]) || in_array(
                    $esame["DES"], // Controllo se il nome dell' esame è nella lista da escludere
                    $esamiNonCarriera
                )) {
                continue;
            }
            //Creao oggetto Esame e aggiungo all'array
            $this->esami[] = new Esame($esame, $cdl, $esamiNonMedia);
        }
        // Ordino per data crescente (dal primo sostenuto)
        usort($this->esami, function ($e1, $e2)
        {
            return $e1->dataSuperamento <=> $e2->dataSuperamento;
        });

        //Calcolo media se NON è di ING. INF.
        // Se ING. INF. lo fa CarrieraLaureandoInf
        if ($cdl != "T. Ing. Informatica") {
            $result = $this->calcolaMedia();
            $this->cfuMedia = $result['cfu'];
            $this->mediaPesata = $result['media'];
        }
    }

    protected function calcolaMedia(): array
    {
        $tot = 0;
        $cfu = 0;
        foreach ($this->esami as $esame) {
            if (!$esame->media) {
                continue;
            }
            $tot += $esame->voto * $esame->cfu;
            $cfu += $esame->cfu;
        }

        if($cfu == 0){
            return ['media' => 0, 'cfu' => 0];
        }
        return [
            'media' => $tot / $cfu,
            'cfu' => $cfu,
        ];
    }

    public function calcolaCfuCurricolari(): int
    {
        $cfu = 0;
        foreach ($this->esami as $esame) {
            $cfu += $esame->cfu;
        }

        return $cfu;
    }
}

