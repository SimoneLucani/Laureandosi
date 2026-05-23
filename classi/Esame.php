<?php
// Classe che rappresenta il singolo esame nella carriera di un laureando
// costruisce a partire dai dati grezzi una struttura organizzata applicando le regola della loda, media, inf

require_once 'ConfigurazioneSistema.php';

class Esame
{
    public string $nome;
    public string $dataSuperamento;
    public int $voto;
    public int $cfu;
    public bool $media; //indica se l'esame fa media
    public bool $inf; //indica se l'esame è informatico

    public function __construct(array $carrieraGrezza, string $cdl, array $esamiNonMedia)
    {
        $this->nome = $carrieraGrezza["DES"]; //es. "Elettrotecnica"
        $this->cfu = $carrieraGrezza["PESO"];
        $voto = $carrieraGrezza["VOTO"];
        // Converto in un timestamp Unix (secondi dal 1970)
        $this->dataSuperamento = strtotime(str_replace("/", "-", $carrieraGrezza["DATA_ESAME"]));

        // Gestione del voto
        if ($voto == null) {
            $voto = 0;
        } else {
            if ($voto == "30  e lode") {
                //Il valore della lode è configurabile e varia tra i cdl
                $voto = ConfigurazioneSistema::getValoreLode($cdl);
            }
        }
        $this->voto = (int)$voto;

        // Calcolo dei flag media e inf
        $this->media = true;
        $this->inf = false;
        //si veriica se l'esame faccia media
        if ($this->voto == 0 || in_array($this->nome, $esamiNonMedia)) {
            $this->media = false;
        }
        //verifica se l'esame sia informatico
        if ($cdl == "T. Ing. Informatica" && in_array($this->nome, ConfigurazioneSistema::getEsamiInformatici())) {
            $this->inf = true;
        }
    }
}