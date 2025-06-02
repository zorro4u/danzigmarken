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

    protected static function questions () {
        for ($a=1; $a<10; $a++) {
            for ($b=1; $b<10; $b++) {
                if ($a+$b > 0 && $a+$b < 10) {
                    $questions []= ["{$a} + {$b} =", $a+$b];
                }
            }
        }
        return $questions;
    }

	public static function getAnswerById($id){
		#global $questions;
		#return $questions[$id][1];
		return static::questions()[$id][1];
	}	
	
	public static function getRandomQuestion(){
		#global $questions;
		#$rand = rand(0, count($questions)-1);
		#return [$rand, $questions[$rand][0]];
		$rand = rand(0, count(static::questions())-1);
		return [$rand, static::questions()[$rand][0]];
	}
	
}
