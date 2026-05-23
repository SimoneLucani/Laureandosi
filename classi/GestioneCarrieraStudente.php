<?php
//Legge i file JSON in GestioneCarrieraStudente/ che simulano la risposta di un'API universitaria reale
// e li rende oggetti (array)
class GestioneCarrieraStudente
{
    public static function prelevaAnagrafica(string $matricola): ?array
    {
        //prendo il anagrafica.json della matricola da GestioneCarrieraStudente/
        $path = __DIR__."/../GestioneCarrieraStudente/".$matricola."_anagrafica.json";
        if (!file_exists($path)) { //Se non esiste
            return null;
        }
        $json = file_get_contents($path); //salvo il contenuto del file in path come una lunga stringa di testo
        $anagrafica = json_decode($json, true); //Trasformo da testo a array
        return $anagrafica["Entries"]["Entry"] ?? null; //Restituisce l'array con nome,cognome...)
    }

    public static function prelevaCarriera(string $matricola): ?array
    {
        $path = __DIR__."/../GestioneCarrieraStudente/".$matricola."_esami.json";
        if (!file_exists($path)) { //Se non esiste
            return null;
        }
        $json = file_get_contents($path); //salvo il contenuto del file in path come una lunga stringa di testo
        $esami = json_decode($json, true);

        return $esami["Esami"]["Esame"];
        //Restituisce array multidimensionale ogni indice numerico(UNO PER OGNI ESAME) mi fa accedere ad un array interno,
        // associativo.
        //es con [0] accedo a ["matricola"=>123456, "des"=>"ELETTROTECNICA"...]
        //in questo modo posso utilizzare un ciclo foreach per scansionare tutta la carriera
    }
}