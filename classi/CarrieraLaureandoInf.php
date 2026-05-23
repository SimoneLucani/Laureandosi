<?php
// Sottoclasse specializzata per gli studenti di Ing. Inf.
// estende CarrieraLaureando aggiungendo:
// -bonus (rimozione esame col voto più basso)
// -media informatica
require_once 'CarrieraLaureando.php';

class CarrieraLaureandoInf extends CarrieraLaureando
{
    public bool $bonus;
    public float $mediaInformatica;


    public function __construct($matricola, $cdl, $dataLaurea)
    {
        // Chiamo il genitore CarrieraLaureando
        parent::__construct($matricola, $cdl, $dataLaurea);
        //86400 secondi in un giorno, 3.6 = anni per essere in pari(bonus)
        if ($this->dataLaurea - 3.6 * 365 * 86400 < $this->dataIscrizione)
        {
            $this->bonus = true; //assegno bonus
        } else {
            $this->bonus = false; //niente bonus
        }
        if ($this->bonus) {
            $this->togliVotoPiuBasso();//se ha diritto al bonus, tolgo il voto più basso
        }
        //ora che è stato eventualmente rimosso l'esame dal voto più basso è possibile calcolare le medie pesate
        $result = $this->calcolaMedia();
        $this->cfuMedia = $result['cfu'];
        $this->mediaPesata = $result['media'];
        //e calcolo anche la media pesata degli esami informatici
        $this->mediaInformatica = $this->calcolaMediaInf();
    }

    
    //per trovare il più impattante negativamente, prima calcolo la media totale,
    // e poi simulo la media rimuovendo uno alla volta un esame dal calcolo
    private function togliVotoPiuBasso(): void
    {
        $mediaMassimaTrovata = -1.0;
        $nomeEsameDaRimuovere = null;

        // Calcolo media pesata e cfu totali attuali(senza bonus) della carriera (solo per gli esami che fanno media)
        $mediaAttuale = 0;
        $cfuTotaliAttuali = 0;
        foreach ($this->esami as $esame) {
            if ($esame->media) {
                $mediaAttuale += ($esame->voto * $esame->cfu);
                $cfuTotaliAttuali += $esame->cfu;
            }
        }

        // Se non ci sono CFU che fanno media, non rimuovo nulla
        if ($cfuTotaliAttuali == 0) return;

        // Simulo la rimozione di OGNI esame per trovare il vantaggio maggiore
        foreach ($this->esami as $esame) {
            if (!$esame->media) continue;

            // Calcolo cosa succederebbe alla media senza questo esame specifico
            $mediaRimanente = $mediaAttuale - ($esame->voto * $esame->cfu);
            $cfuRimanenti = $cfuTotaliAttuali - $esame->cfu;

            if ($cfuRimanenti > 0) {
                $mediaSimulata = $mediaRimanente / $cfuRimanenti;

                // Se questa rimozione garantisce la media più alta vista finora, la salvo
                if ($mediaSimulata > $mediaMassimaTrovata) {
                    $mediaMassimaTrovata = $mediaSimulata;
                    $nomeEsameDaRimuovere = $esame->nome;
                }
            }
        }

        // Applico la rimozione definitiva all'esame che ha vinto la simulazione
        if ($nomeEsameDaRimuovere !== null) {
            foreach ($this->esami as $esame) {
                if ($esame->nome == $nomeEsameDaRimuovere) {
                    $esame->media = false;
                    break; // Trovato e rimosso dalla media
                }
            }
        }
    }

    private function calcolaMediaInf(): float
    {
        $tot = 0;
        $cfu = 0;
        foreach ($this->esami as $esame) {
            if (!$esame->media || !$esame->inf) {
                continue;
            }
            $tot += $esame->voto * $esame->cfu;
            $cfu += $esame->cfu;
        }
        if ($cfu == 0) {
            return 0;
        }

        return $tot / $cfu;
    }
}