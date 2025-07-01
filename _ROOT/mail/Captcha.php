<?php
namespace Dzg\Mail;

#########################################################################
#	Kontaktformular.com         					                    #
#	http://www.kontaktformular.com        						        #
#	All rights by KnotheMedia.de                                    	#
#-----------------------------------------------------------------------#
#	I-Net: http://www.knothemedia.de                            		#
#########################################################################



/***********************
 * Summary of Captcha
 * 
 * wird für Bilderzeugung in assets/inc/captcha.php benötigt
 * und in der Kontakt-Klasse
 */
class Captcha
{
	public CONST KEY="12h49m30s";


	public static function getText()
	{
		// Wortliste erstellen
		$alphabet = self::MakeAlphabet();

		// Array des Alphabets durchwürfeln
		shuffle($alphabet);

		// Die ersten 4 Zeichen der geshuffelten Wortliste
		$text = '';
		for ($i=0; $i<4; $i++) {
			$text .= $alphabet[$i];
		}
		return $text;
	}


	private static function MakeAlphabet()
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


    public static function encrypt(string $string): string
    {
        $result = "";
        $key_len = strlen(self::KEY);
        $str_len = strlen($string);
        for ($i=0; $i < $str_len; $i++) {
            // einzelnes Zeichen des String
            $char = substr($string, $i, 1);

            // einzelnes Zeichen des Keys; rotierend, beginnend mit letztem Zeichen
            $keychar = substr(self::KEY, ($i % $key_len)-1, 1);

            // Summe aus ASCII-Code Stringzeichen, Keyzeichen --> Summenzahl -> neues Stringzeichen
            $charX = ord($char) + ord($keychar);
            $result .= chr($charX);
        }
        $result = base64_encode($result);
        str_replace("=", "", $result);
        return $result;
    }

}