<?php
require_once "API.php"; //Come copia e incolla di API.php in index.php
// Quindi ad ogni POST dal Browser, prima viene eseguito tutto API.php (legge POST, aggiorna SESSION)
// Viene generato HTML in base ad i SESSION appena impostati da API
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Generatore Prospetti di Laurea</title>
    <!--prendo URL della cartella del tema attivo-->
    <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/style.css">
</head>

<!--attributo dinamico nel div
    Utilizzo alcune variabili di $_SESSION impostate da API.php-->
<body <?php echo $_SESSION["modalita"] ?? ''; ?>>

<!--dati del form inviati nel corpo del messaggio, non visibili nell' URL, a se stesso-->
<form method="post" action="index.php">

    <div class="titolo-test">
        <b>Generatore Prospetti di Laurea</b><br>
        MODALITA' TEST
    </div>

    <div class="form-container">

        <div class="form-header">
            Generatore Prospetti di Laurea
        </div>

        <div class="form-body">

            <div class="form-column">
                <label class="info" for="cdl"><b>CdL:</b></label>
                <select id="cdl" name="cdl" required>
                    <option value="">Seleziona un CdL</option>
                    <option value="T. Ing. BioMedica">T. Ing. BioMedica</option>
                    <option value="T. Ing. Elettronica">T. Ing. Elettronica</option>
                    <option value="T. Ing. Informatica">T. Ing. Informatica</option>
                    <option value="T. Ing. delle Telecomunicazioni">T. Ing. delle Telecomunicazioni</option>
                    <option value="M. Ing. Biomedica, Bionics Engineering">M. Ing. Biomedica, Bionics Engineering</option>
                    <option value="M. Ing. Elettronica">M. Ing. Elettronica</option>
                    <option value="M. Computer Engineering, Artificial Intelligence and Data Enginering">M. Computer Engineering, Artificial Intelligence and Data Enginering</option>
                    <option value="M. Ing. Robotica e della Automazione">M. Ing. Robotica e della Automazione</option>
                    <option value="M. Ing. delle Telecomunicazioni">M. Ing. delle Telecomunicazioni</option>
                </select>

                <label class="info" for="data"><b>Data di laurea: </b></label>
                <input type="date" id="data" name="data" required>
            </div>

            <div class="form-column">
                <label class="info" for="matricole"><b>Matricole:</b></label>
                <textarea id="matricole" title="matricole" name="matricole" required></textarea>
            </div>

            <div class="form-column">
                <button type="submit" name="crea">Crea Prospetti</button>

                <!-- API salva in SESSION[link] l'URL del documento generato, vuoto finché non vengono creati -->
                <?php if (!empty($_SESSION['link'])): ?>
                    <a href="<?php echo $_SESSION['link']; ?>" target="_blank" class="link-prospetto">
                        <p>Visualizza Prospetti</p>
                    </a>
                <?php else: ?>
                    <a class="link-prospetto disabilitato">
                        <p>Visualizza Prospetti</p>
                    </a>
                <?php endif; ?>

                <button type="submit" name="invia" formnovalidate <?php echo $_SESSION["disabilitato"] ?? ''; ?>>Invia Prospetti</button>

                <p style="color: green; font-weight: bold;"><?php echo $_SESSION["stato"] ?? ''; ?></p>
            </div>

        </div>
    </div>

    <!-- Stampo tutto ciò che è stato accumulato in $_SESSION["staoErrori"]-->
    <div class="error-section">
        <p style="text-align: left;">
            <?php echo $_SESSION["statoErrori"] ?? ''; ?>
        </p>
    </div>

</form>

</body>
</html>