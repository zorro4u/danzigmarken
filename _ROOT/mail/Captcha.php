<?php
namespace Dzg\Mail;

#########################################################################
#	Kontaktformular.com         					                    #
#	http://www.kontaktformular.com        						        #
#	All rights by KnotheMedia.de                                    	#
#-----------------------------------------------------------------------#
#	I-Net: http://www.knothemedia.de                            		#
#########################################################################

#require_once __DIR__.'/QuestionBlocker.php';
#use Dzg\Mail\QuestionBlocker;

#require_once __DIR__.'/../cls/Kontakt.php';
#use Dzg\Kontakt;

require $_SERVER['DOCUMENT_ROOT']."/assets/vendor/autoload.php";
use Gregwar\Captcha\CaptchaBuilder;


/***********************
 * Summary of Captcha
 *
 * wird für Bilderzeugung in assets/inc/captcha.php benötigt
 * und in der Kontakt-Klasse
 */
class Captcha
{
	private const KEY = "12h49m30s";

	/***********************
	 * Summary of encrypt
	 * codiert eine Zeichenkette;
	 * wird genutzt, um den aktuellen Buchstaben-Code zu speichern
	 * und dann mit Benutzereingabe zu vergleichen
	 *
	 * @param string $string
	 * @return string
	 */
    public static function encrypt($string): string
    {
		$string = (empty($string)) ? "" : $string;
		$key = self::KEY;
        $charX = "";
        $key_len = strlen($key);
        $str_len = strlen($string);

        for ($i=0; $i < $str_len; $i++) {
            // einzelnes Zeichen des Strings
            $char = substr($string, $i, 1);

            // einzelnes Zeichen des Keys; rotierend, beginnend mit letztem Zeichen
            $keychar = substr($key, ($i % $key_len)-1, 1);

            // Summe aus ASCII-Code String- & Keyzeichen --> Summenzahl -> neues ASCII-Zeichen
            $data = ord($char) + ord($keychar);
            $charX .= chr($data);
        }

        $result = base64_encode($charX);
        $result = str_replace("=", "", $result);
        return $result;
    }


    public static function decrypt(string $encrypted, int $len_original): string
    {
		$key = self::KEY;
        $charX = "";
        $key_len = strlen($key);
        $str_len = strlen($len_original);
		$result = "";
		$data = base64_decode($encrypted);

		for ($i=0; $i < $str_len; $i++) {
            // einzelnes Zeichen des Strings
            $char = substr($data, $i, 1);

            // einzelnes Zeichen des Keys; rotierend, beginnend mit letztem Zeichen
            $keychar = substr($key, ($i % $key_len)-1, 1);

            // Summe aus ASCII-Code String- & Keyzeichen --> Summenzahl -> neues ASCII-Zeichen
            $dataX = ord($char) - ord($keychar);
            $charX .= chr($dataX);
		}
		$result = $charX;

		return $result;
	}

	/***********************
	 * Summary of getPic
	 *
	 * erzeugt CAPTCHA Bild
	 * benötigt dafür in der Apache Config (php.ini) die GD-extension!
	 * wird in assets/inc/captcha.php gestartet
	 */
	public static function getPic()
	{
		// Buchstaben-Kombi
		#$text = self::getText();
		#$_SESSION['phrase'] = $text;
		#$_SESSION['phrase'] = self::encrypt($text);

		// Rechenaufgabe
		# $quest = $_SESSION['captcha_frage'][1];
		# [$id, $quest] = $_SESSION['captcha_frage'];
		[$id, $quest] = QuestionBlocker::getRandomQuestion();
		$answ = QuestionBlocker::getAnswerById($id);
		$text = str_replace(" = ?", "", $quest);
		$_SESSION['phrase'] = $answ;
		#$_SESSION['phrase'] = self::encrypt($answ);
		#$_SESSION['captcha'] = [$id, $quest, $answ];

		#header('Content-type: text/plain; charset=utf-8');
		header('Content-type: image/png');
		$img = imagecreatefrompng($_SERVER['DOCUMENT_ROOT']."/assets/pic/captcha.png"); 	# Hintergrundbild
		$color = imagecolorallocate($img, 0, 0, 0); 	# Farbe
		#$ttf = $_SERVER['DOCUMENT_ROOT']."/assets/fonts/Imperator.ttf";		# ohne '+='
		#$ttf = $_SERVER['DOCUMENT_ROOT']."/assets/fonts/Inkspot.ttf";	# 25, für Buchstaben, große Datei
		$ttf = $_SERVER['DOCUMENT_ROOT']."/assets/fonts/Sandyshand.ttf"; # 25, für Rechnung
		#$ttf = $_SERVER['DOCUMENT_ROOT']."/assets/fonts/SA-BostonBlvd.ttf"; # 45, für Rechnung

		$ttfsize = 25; 		# Schriftgrösse
		$angle = rand(0, 5);
		$t_x = rand(5, 50);
		$t_y = 35;
		imagettftext($img, $ttfsize, $angle, $t_x, $t_y, $color, $ttf, $text);
		imagepng($img);
		imagedestroy($img);
	}

	// mit CAPTCHA Bibliothek (sicherer, teilsweise aber schwer zu lesen)
	// muss in Kontakt.php auch aktiviert sein
	public static function getPic0()
	{
		$captcha = new CaptchaBuilder;
		$_SESSION['phrase'] = $captcha->getPhrase();

		header('Content-Type: image/jpeg');
		$captcha->build()->output();
	}



	private static function getText(): string
	{
		// Wortliste erstellen
		$alphabet = self::makeAlphabet();

		// Array des Alphabets durchwürfeln
		shuffle($alphabet);

		// Die ersten 4 Zeichen der geshuffelten Wortliste
		$text = '';
		for ($i=0; $i<4; $i++) {
			$text .= $alphabet[$i];
		}
		return $text;
	}


	private static function makeAlphabet(): array
	{
		// Grossbuchstaben erzeugen ohne "L", "I", "O"
		for ($x = 65; $x <= 90; $x++) {
			if($x != 73 && $x != 76 && $x != 79)
				$alphabet[] = chr($x);
		}

		// Kleinbuchstaben erzeugen ohne "l", "i", "0"
		for ($x = 97; $x <= 122; $x++) {
			if($x != 105 && $x != 108 && $x != 111)
				$alphabet[] = chr($x);
		}

		// Zahlen erzeugen ohne "0", "1"
		for ($x = 48; $x <= 57; $x++) {
			if($x != 48 && $x != 49)
				$alphabet[] = chr($x);
		}

		return $alphabet;
	}

}