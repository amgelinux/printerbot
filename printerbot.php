<?php
//
// Basado en la librería https://github.com/skrtdev/NovaGram
//



// Para que rellenes el .env
//
// $mitoken = "TUTOKEN";
// $lista_usuarios = ['Usuario1', "Usuario2"];
// $carpetabase = "./Docs/";
// $impresora = "SCX-4300-Series";
//


require ".env";

if (file_exists('vendor')) {
    require 'vendor/autoload.php';
}
else{
    if (!file_exists('novagram.phar')) {
        copy('https://novagram.ga/phar', 'novagram.phar');
    }
    require_once 'novagram.phar';
}

use skrtdev\NovaGram\Bot;
use skrtdev\Telegram\Message;

$Bot = new Bot($mitoken);

$Bot->onCommand('start', function (Message $message) {
    $message->reply("Adjunta un PDF y si te conozco lo imprimo en casa.");
});

// https://exdebian.org/wiki/imprimir-archivos-pdf-o-ps-desde-consola-en-gnulinux-utilizando-el-comando-lp
// Cambiar a $Bot->getFile()
function descargarDocumento($fichero_path, $fichero) {

    global $mitoken;
    $url = "https://api.telegram.org/file/bot$mitoken/$fichero_path";
    if (file_put_contents($fichero, file_get_contents($url))) {
        echo "Fichero $fichero descargado con éxito." . PHP_EOL;
        return true;
    } else {
        echo "Fallo al descargar el fichero $fichero." . PHP_EOL;
        return false;
    }
}

function borrarDocumento($fichero) {
    if (!unlink($fichero)) {
    echo "Fallo al borrar el fichero $fichero" . PHP_EOL;
    }
}

// Imprime el fichero si es pdf, queda quitar el echo y ponerlo en un equipo que imprima
function imprimirFichero($tipofichero, $fichero) {
    global $impresora;
    if ($tipofichero == "application/pdf") {
        exec("/usr/bin/lp -d $impresora -o fitplot $fichero");
    }
}


$Bot->addErrorHandler(function(Throwable $e) {
    print('Ocurrió un error:'.PHP_EOL);
    print($e);
    print(PHP_EOL);
});


//
// Si se le da a NO comprimir la imagen la trata como un documento (al menos las .png)
// Si se le da a la opción de comprimir la trata como una photo
//
$Bot->onMessage(function (Message $message) use ($mitoken, $Bot) { // update is a message and contains text
    global $lista_usuarios;
    global $carpetabase;

    print_r($message);
    if (in_array($message->from->username, $lista_usuarios)) {

// Si el mensaje tiene un document
//
        if (isset($message->document)) {
            $destino = $carpetabase . $message->document->file_name;
            if (descargarDocumento($Bot->getFile($message->document->file_id)->file_path, $destino)) {
                imprimirFichero($message->document->mime_type, $destino);
  //              borrarDocumento($destino);
            };
            $Bot->sendMessage($message->from->id, montarRespuesta($message));
        }

// Si el mensaje tiene una photo
        if (isset($message->photo)) {
            print_r("Se trata como foto: " . $message->photo[2]->file_id);
//
// ESCRIBA AQUI SU CODIGO
//
        }
    };

});

function montarRespuesta(Message $mensaje) {
    $mensajeretorno = "Documento recibido" . PHP_EOL . "==================" . PHP_EOL;
    $mensajeretorno .= "> ". $mensaje->document->file_name . " (". $mensaje->document->mime_type . ")" . PHP_EOL;
    $mensajeretorno .= $mensaje->document->file_size. " Bytes" . PHP_EOL;
    return $mensajeretorno;
}




$Bot->start();

//  Información: https://blogthinkbig.com/crear-bot-de-telegram-botfather
//  About Telegram bots: https://core.telegram.org/bots
//  Bot API manual: https://core.telegram.org/bots/api#file <--
?>
