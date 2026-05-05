<?php
namespace Dzg\Import;

date_default_timezone_set('Europe/Berlin');

# echo ToolBox::time2str(216105.3);   // 2d 12h 1m


/*
from dataclasses import dataclass
#from os import system
#system("color")

# ANSI escape code:
# \33[4C jump 4x right
# \33[1A jump 1x up
# A B C D : up, down, right, left
*/


/**
 * Sammlung von Helfer-Routinen
 */
class ToolBox
{
    /**
     * Wandelt eine Zahl in formatierten Text um.
     *
     * TimeConverter
     * 0.321456 -> '0.321 sec'
     * 5.321456 -> '5.32 sec'
     * 45.32145 -> '45.3 sec'
     * 105.3214 -> '1m 41s'
     * 216105.3 -> '2d 12h 1m'
     */
    public static function time2str(float $sec_to_convert): string
    {
        $day = $hour = $minute = 0;
        $str_time = '';

        # comma formating
        if ($sec_to_convert < 0.001) {
            $second = round($sec_to_convert, 5);
        }
        elseif ($sec_to_convert < 0.5) {
            $second = round($sec_to_convert, 3);
        }
        elseif ($sec_to_convert < 10) {
            $second = round($sec_to_convert, 2);
        }
        elseif ($sec_to_convert < 60) {
            $second = round($sec_to_convert, 1);
        }
        else {
            $second = (int)round($sec_to_convert);
        };

        # second output, comma formated
        $time_liste = [$second, ' sec '];

        # splitted output above one minute
        if ($second >= 60) {
            [$minute, $second] = self::divmod($second, 60);
            $time_liste = [$minute, 'm ', $second, 's '];
        };

        if ($minute >= 60) {
            [$hour, $minute] = self::divmod($minute, 60);
            $time_liste = [$hour, 'h ', $minute, 'm '];
        };

        if ($hour >= 24) {
            [$day, $hour] = self::divmod($hour, 24);
            $time_liste = [$day, 'd ', $hour, 'h ', $minute, 'm '];
        };

        # list to string
        $str_time = implode($time_liste);

        return trim($str_time);
    }


    /**
     * ganzzahlige Division mit ganzzahligem Restwert
     */
    public static function divmod(int $dividend, int $divisor): array
    {
        return [intdiv($dividend, $divisor), $dividend % $divisor];
    }
}


/**
 * ANSI escape foreground color (30-37)/90-97
 */
/*
class ColorList
{
    // echo ColorList::yellow . "Text" . ColorList::off;

    $grey    = "\033[90m";
    $red     = "\033[91m";
    $green   = "\033[92m";
    $yellow  = "\033[93m";
    $blue    = "\033[94m";
    $magenta = "\033[95m";
    $cyan    = "\033[96m";
    $white   = "\033[97m";
    off      = "\033[0m";
}
*/


/**
 * A simple and fast progress indicator
 *
 * pb = ProgressBar(repeats)
 * pb.start()
 * loop(repeats):
 * pb.update()
 * pb.close()
 */
/*
class ProgressBar
{

    def __init__(self, total, **kwargs):
        # input arguments
        repeats  = total if total else 1        # total iterations
        char_max = kwargs.get('char_max', 26)   # length of progress bar / even number
        char_off = kwargs.get('char_off', 4)    # first offset / even
        width = kwargs.get('width', 2)          # width of bar step
        char  = kwargs.get('char', "*")         # sign of progress bar

        steps = int((char_max - char_off) / width)      # parts of bar
        step  = repeats // steps if repeats > 9 else 1  # number of values per bar, no zero
        step += 1 if repeats % steps else 0             # manage the rest of values
        rest  = steps - repeats // step

        self.trigger  = 0   # trigger for bar output
        self.char_off = char_off
        self.char  = char
        self.width = width
        self.step  = step
        self.rest  = rest

        #self.start()

    def start(self):
        """ bar offset for the beginning 0%
        """
        print(self.char * self.char_off, end="")

    def update(self):
        """ progress bar output
        """
        self.trigger += 1
        if self.trigger == self.step:
            self.trigger = 0
            print(f"{self.char * self.width}", end="", flush=True)

    def close(self):
        """ fill up to char_max
        """
        print(self.char * self.width * self.rest)
}
*/


## -------- E N D E -------- ##


// EOF