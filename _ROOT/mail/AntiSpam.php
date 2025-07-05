<?php
namespace Dzg;
require_once __DIR__.'/QuestionBlocker.php';
require_once __DIR__.'/RateLimiting.php';
require_once __DIR__.'/Captcha.php';


/*
Namenschreibweise:
ClassName	- [PascalCase]
methodName	- [camelCase]
property_name - [snake_case]
functionName (global functions)- [camelCase]
$variable_name - [snake_case]
CONST_NAME - [UPPER_SNAKE_CASE]
url - [kebab-case]
file - [snake_case]
*/


/***********************
 * Summary of AntiSpam
 */
class AntiSpam
{
	/***********************
	 * Summary of getRandomQuestion
	 * liefert eine zufällige Frage und deren Index
	 */
	public static function getRandomQuestion(): array {
        return QuestionBlocker::getRandomQuestion();
	}


	/***********************
	 * Summary of getAnswerById
	 * liefert die Antwort zu der Frage (Index)
	 */
	public static function getAnswerById($id): int {
        return QuestionBlocker::getAnswerById($id);
	}


	/***********************
	 * Summary of rateLimiting
	 * Aufrufe pro IP Adresse limitieren
	 */
    public static function rateLimiting() {
        return RateLimiting::run();
	}


	/***********************
	 * Summary of loadCaptchaPic
	 *
	 * erzeugt CAPTCHA Bild
	 * <img src='/assets/inc/captcha.php' />
	 * captcha.php  --> AntiSpam::loadCaptchaPic()
	 * AntiSpam.php --> Captcha::getPic()
	 */
	public static function loadCaptchaPic() {
        return Captcha::getPic();
	}


	/***********************
	 * Summary of encryptCaptchaText
	 *
	 * @param string $string
	 * @return string
	 */
    public static function encrypt($string): string {
        return Captcha::encrypt($string);
	}

    public static function decrypt(string $encrypted, int $len_original): string {
        return Captcha::decrypt($encrypted, $len_original);
	}

}
