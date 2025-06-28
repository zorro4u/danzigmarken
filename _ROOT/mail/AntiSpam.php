<?php
namespace Dzg\Mail;

#header('Content-type: text/html; charset=utf-8');
/*
$questions = [
	0 => ["5 + 5 =", 10],
	1 => ["6 + 2 =", 8],
	2 => ["2 + 2 =", 4],
	3 => ["8 + 1 =", 9],
	4 => ["3 + 4 =", 7]
];
*/


class AntiSpam {
	private static array $questions_arr;

	/**
	 * Summary of questions
	 * erzeugt ein Frage-Antwort-Array: [["FRAGE", ANTWORT], [..]]
	 * [["0 + 1 =", 1], .. ["9 + 0 =", 9]]
	 * 54 Additionsaufgaben mit einstelligem Ergebnis
	 * letzlich sind immer nur 9 Antworten richtig, 1-9
	 * --> Erraten mit Wahrscheinlichkeit 1:9
	 *
	 * @return array<int|string>[]
	 */
    protected static function questions() :array
	{
		if (empty(self::$questions_arr)) {
			for ($a=0; $a<10; $a++) {
				for ($b=0; $b<10; $b++) {
					// beschränkt auf einstelliges Ergebnis
					if ($a+$b > 0 && $a+$b < 10) {
						$questions []= ["{$a} + {$b} =", $a+$b];
					}
				}
			}
			self::$questions_arr = $questions;
		}
        return self::$questions_arr;
    }


	/**
	 * Summary of getRandomQuestion
	 * liefert eine zufällige Frage und deren Index
	 */
	public static function getRandomQuestion() :array
	{
		$rand = rand(0, count(self::questions())-1);
		return [$rand, self::questions()[$rand][0]];
	}


	/**
	 * Summary of getAnswerById
	 * liefert die Antwort zu der Frage (Index)
	 */
	public static function getAnswerById($id) :int
	{
		return self::questions()[$id][1];
	}
}
