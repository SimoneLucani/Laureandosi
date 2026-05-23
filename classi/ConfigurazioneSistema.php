<?php
// Legge i tre file JSON della cartella config/ e fornisce metodi per interrogarli
// Le altre classi non accedono direttamente al JSON
class ConfigurazioneSistema
{
    //Leggo i file JSON di configurazione una volta sola, se $initialized=0.
    // Se initialized è a 1, trovo le informazioni di cdl.json, esami-inf.json e filtri.json,
    // già convertiti in array, rispettivamente in $cdlData, $esamiInformatici, $filtriData
    private static $cdlData = null;
    private static $esamiInformatici = null;
    private static $filtriData = null;

    private static  $initialized = false;
    //carico i file JSON una sola volta

    // Funzione che carica il JSON negli array della classe, restituendo un array vuoto in caso di errore
    private static function caricaJson(string $filePath): array
    {
        // Prima di aprirlo,controllo se esiste
        if (!file_exists($filePath)) {
            return [];
        }

        $json = file_get_contents($filePath);//converte il file in stringa di testo
        $data = json_decode($json, true);//converte la stringa JSON in array associativo(grazie al true)
        //true forza la convezione in array invece che in oggetto

        // Controllo
        return is_array($data) ? $data : [];
    }

    // Inizializzazione dei dati dai file json se non già caricati
    private static function init(): void
    {
        // Carico i dati se non li ho già
        if (!self::$initialized) {
            // DIR costante che indica la cartella del file corrente
            self::$cdlData = self::caricaJson(__DIR__."/../config/cdl.json");
            self::$esamiInformatici = self::caricaJson(__DIR__."/../config/esami_inf.json");
            self::$filtriData = self::caricaJson(__DIR__."/../config/filtri.json");
            self::$initialized = true;
        }
    }

    public static function getEsamiNonCarriera(string $cdl, ?string $matricola = null): array
    {
        self::init();

        // Da togliere = ne media ne cfu
        // No media = si cfu, no media
        // Filtro globale da togliere prima (es Test Valutazione)
        $esamiDaTogliere = self::$filtriData[$cdl]["*"]["da_togliere"] ?? [];

        // Regola specifica per la matricola
        if ($matricola && isset(self::$filtriData[$cdl][$matricola]["da_togliere"])) {
            $esamiDaTogliere = array_merge($esamiDaTogliere, self::$filtriData[$cdl][$matricola]["da_togliere"]);
        }

        // Prendo le regole globali, quelle specifiche della matricola e le unisco con array_merge

        return is_array($esamiDaTogliere) ? $esamiDaTogliere : [];
    }

    // Stessa logica di getEsamiNonCarriera, ma legge il campo "non_media" del JSON
    // Chiamata da Esame per il calcolo della media
    public static function getEsamiNonMedia(string $cdl, string $matricola = null): array
    {
        self::init();

        $esamiNonMedia = self::$filtriData[$cdl]["*"]["non_media"] ?? [];

        if ($matricola && isset(self::$filtriData[$cdl][$matricola]["non_media"])) {
            $esamiNonMedia = array_merge($esamiNonMedia, self::$filtriData[$cdl][$matricola]["non_media"]);
        }

        return is_array($esamiNonMedia) ? $esamiNonMedia : [];
    }

    public static function getEsamiInformatici(): array
    {
        self::init();

        return is_array(self::$esamiInformatici) ? self::$esamiInformatici : [];
    }

    public static function getCfuCurricolari(string $cdl): int
    {
        self::init();

        return self::$cdlData[$cdl]["crediti_totali"] ?? 0;
    }

    public static function getValoreLode(string $cdl): int
    {
        self::init();

        return self::$cdlData[$cdl]["valore_lode"] ?? 0;
    }

    public static function getFormulaVoto(string $cdl): string
    {
        self::init();

        return self::$cdlData[$cdl]["formula"] ?? "";
    }

    public static function getInfoParametro(string $cdl): array
    {
        self::init();

        return self::$cdlData[$cdl]["info_parametro"] ?? [];
    }

    public static function getMessaggioProspetto(string $cdl): string
    {
        self::init();

        return self::$cdlData[$cdl]["msg_commissione"] ?? "Messaggio non disponibile.";
    }

    public static function getEmailBody(string $cdl): string
    {
        self::init();

        return self::$cdlData[$cdl]["corpo_email"] ?? "Corpo email non disponibile.";
    }
}
