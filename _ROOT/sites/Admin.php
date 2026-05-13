<?php
namespace Dzg;

require_once __DIR__.'/../siteform/admin.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/**
 * Summary of Class Admin
 * class A extends B implements C
 */
class Admin extends AdminForm
{
    public static function show(): void
    {
        self::siteEntryCheck();
        self::dataPreparation();
        self::formEvaluation();

        Header::show();
        self::show_body();
        Footer::show("account");

        # self::lastScriptAusgeben();
    }


    /**
     * HTML Ausgabe
     */
    private static function show_body(): void
    {
        $msg = self::MSG;
        $show_form = self::$show_form;
        $status_message = self::$status_message;

        $output = "<div class='container main-container'>";

        if (!$show_form):
            $output .= $status_message;

        # Seiten-Check okay, Seite anzeigen
        else:
            $output .= "<h2>{$msg[310]}</h2><br>";
            $output .= $status_message;

            $output .= "<div>";    // START
            $output .= self::tab_selection();

            $output .= "<div class='tab-content'>";
            $output .= self::tab_info();
            $output .= self::tab_user();
            $output .= self::tab_autologin();
            $output .= self::tab_register();
            $output .= self::tab_sonstiges();
            $output .= self::tab_tools();

            $output .= "</div> ";   // tab-content
            $output .= "</div> ";   // START


        /*
        <div style='
            display: grid;
            justify-content: center;
            padding: 30px 30px;
            '>
        <a href='https://www.worldflagcounter.com/details/iMx'>
        <img src='https://www.worldflagcounter.com/iMx/' alt='Flag Counter'></a>
        *//*
        https://www.worldflagcounter.com/iMx/
        https://www.worldflagcounter.com/details/iMx
        https://www.worldflagcounter.com/regenerate/iMx
        O1nJ8Z5bY
        *//*
        </div>
        */

        endif;                // Seite anzeigen
        $output .= "</div>";  // container main-container

        // HTML Ausgabe
        //
        echo $output;
    }



    private static function tab_selection(): string
    {
        $msg = self::MSG;
        $active = self::$active;

        return <<<EOT
        <ul class='nav nav-tabs' role='tablist'>
        <li role='presentation' class='{$active['info']}'>
        <a href='#info' aria-controls='info' role='tab' data-toggle='tab'>
        {$msg[311]}</a></l>

        <li role='presentation' class='{$active['user']}'>
        <a href='#user' aria-controls='user' role='tab' data-toggle='tab'>
        {$msg[312]}</a></l>

        <li role='presentation' class='{$active['autologin']}'>
        <a href='#autologin' aria-controls='autologin' role='tab' data-toggle='tab'>
        {$msg[313]}</a></li>

        <li role='presentation' class='{$active['regis']}'>
        <a href='#regis' aria-controls='regis' role='tab' data-toggle='tab'>
        {$msg[314]}</a></li>

        <li role='presentation' class='{$active['sonst']}'>
        <a href='#sonst' aria-controls='sonst' role='tab' data-toggle='tab'>
        {$msg[315]}</a></li>

        <li role='presentation' class='{$active['tools']}'>
        <a href='#tools' aria-controls='tools' role='tab' data-toggle='tab'>
        {$msg[316]}</a></li>
        </ul>
        EOT;
    }


    private static function tab_info(): string
    {
        $msg = self::MSG;
        $active = self::$active;
        $usr_data = self::$usr_data;
        $login_data = self::$login_data;
        $log_data = self::$log_data;
        $out_token = "";
        $last_seen = "";

        $name = htmlspecialchars($usr_data['username'], ENT_QUOTES);
        $mail = htmlspecialchars($usr_data['email'], ENT_QUOTES);
        $date = date('d.m.y H:i', strtotime($usr_data['created']));

        #weitere Informationen:
        #htmlspecialchars($usr_data['status'], ENT_QUOTES)
        $act = "";
        if ($usr_data['status'] === "activated") {
            $act = $msg[317];
        } elseif (!empty($usr_data['status'])) {
            $act = $msg[318];
        };

        $changed = (!empty($usr_data['changed']))
            ? date('d.m.y H:i', strtotime($usr_data['changed']))
            : "";
        $endtime = (!empty($login_data['token_endtime']))
            ? date('d.m.y H:i', strtotime($login_data['token_endtime']))
            : "";
        $out_created = (!empty($login_data['created']))
            ? date('d.m.y H:i', strtotime($login_data['created']))
            : "";
        $out_ip = (!empty($login_data['ip']))
            ? htmlspecialchars($login_data['ip'], ENT_QUOTES)
            : "";

        $out_date = (!empty($log_data['date']))
            ?
            "<table><tr><td>".date('d.m.y H:i', strtotime($log_data['date']))."</td>
            <td>&ensp;#{$log_data['ct_date']}&nbsp;</td></tr>
            <tr><td>{$log_data['ip']}</td>
            <td>&ensp;#{$log_data['ct_last']}</td>
            </tr></table>"
            : "";

        $out_ident = (!empty($login_data['identifier']))
            ? htmlspecialchars($login_data['identifier'], ENT_QUOTES)
            : "";

        $autologin = (!empty($login_data['autologin'])) ? "*" : "";
        $out_group = $log_data['ct_group'] ?? "0";
        $out_singl = $log_data['ct_singl'] ?? "0";
        $returns   = ($out_singl)
            ? 100 - round($out_group / $out_singl * 100)
            : '-';
        $out_black = $log_data['ct_black'] ?? "0";
        $out_block = $log_data['ct_block'] ?? "0";
        $out_nblck = (!empty(self::$new_blocked))
            ? "&nbsp;/&nbsp;+" . self::$new_blocked
            : "";

        /*
        $out_token = (!empty($login_data['token_hash'])) ? htmlspecialchars($login_data['token_hash'], ENT_QUOTES) : "";
        $last_seen = (!empty($login_data['changed'])) ? date('d.m.y H:i', strtotime($login_data['changed'])) : "";
        */

        return <<<EOT
        <div role='tabpanel' class='tab-pane {$active['info']}' id='info'>
        <p><br></p>

        <table>
        <tr><td>{$msg[319]}:&nbsp;</td>
            <td>{$name}</td></tr>
        <tr><td>{$msg[320]}:&nbsp;</td>
            <td>{$mail}</td></tr>

        <!--
        <tr><td>{$msg[321]}:&nbsp;</td><td>{$date}</td></tr>
        <tr><td>{$msg[322]}:&nbsp;</td><td>{$changed}</td></tr>
        -->

        <tr><td>Login{$autologin}:&nbsp;</td>
            <td>{$out_created}</td></tr>
        <tr><td>last.ip:&nbsp;</td>
            <td>{$out_ip}</td></tr>

        <!--<tr><td>{$msg[323]}:</td><td>{$endtime}</td></tr>-->
        <!--<tr><td>AutoIdent:</td><td>{$out_ident}</td></tr>-->
        <!--<tr><td>Token:</td><td>{$out_token}</td></tr>-->
        <!--<tr><td>last.seen:</td><td>{$last_seen}</td></tr>-->

        <tr><td>&nbsp;</td><td></td></tr>
        <tr><td>last:&nbsp;</td>
            <td>{$out_date}</td></tr>
        <tr><td>&nbsp;</td><td></td></tr>
        <tr><td>log:&nbsp;</td>
            <td>{$out_group}&nbsp;/&nbsp;{$returns}%&nbsp;returns</td></tr>
        <tr><td>block:&nbsp;</td>
            <td>{$out_block}{$out_nblck}</td></tr>
        </table>

        <br><hr>
        <form method='POST'>
            <button formaction='../tools/showlog' class='btn btn-primary' type=''>{$msg[324]}</button>&emsp;&emsp;
        </form>

        </div>
        EOT;
    }


    private static function tab_user(): string
    {
        $msg = self::MSG;
        $active = self::$active;
        $user_list = self::$user_list;

    $output = <<<EOT
    <div role='tabpanel' class='tab-pane {$active['user']}' id='user'>
    <p><br></p>

    <form action='?save=delete_user&tab=user' method='POST' class='form-horizontal'>
    <div class='panel panel-default'>

    <table class='table'>
    <tr>
    <th>#</th>
    <th></th>
    <th>User</th>
    <th>Email</th>
    <th>Login</th>
    <th>Auto</th>
    <th>last.seen</th>
    <th>last.ip</th>
    </tr>
    EOT;

        $ct = 0;
        foreach ($user_list as $user):
            $last_seen = (!empty($user['last_seen']))
                ? date('d.m.y H:i', strtotime($user['last_seen']))
                : "";
            $ct++;

    $output .= <<<EOT
    <tr>
    <td>{$ct}</td>
    <td>
    <input type='radio' id='usr{$ct}' name='usrchoise' value='{$ct}' autocomplete='off' />
    <label for='usr{$ct}'></label></td>
    <td>{$user['username']}</td>
    <td>{$user['email']}</td>
    <td>{$user['ct_login']}</td>
    <td>{$user['ct_autologin']}</td>
    <td>{$last_seen}</td>
    <td>{$user['last_ip']}</td>
    </tr>
    EOT;

        endforeach;

    $output .= <<<EOT
    </table>
    </div>
    <button type='submit' class='btn btn-primary'
        onclick='return confirm("{$msg[325]}")'>{$msg[326]}</button>
    </form>
    </div>
    EOT;

        return $output;
    }


    private static function tab_autologin(): string
    {
        $msg = self::MSG;
        $active = self::$active;
        $ct10 = self::$counter[10];
        $ct11 = self::$counter[11];
        $ct12 = self::$counter[12];
        $ct13 = self::$counter[13];
        $ct20 = self::$counter[20];
        $ct21 = self::$counter[21];
        $ct22 = self::$counter[22];
        $ct23 = self::$counter[23];
        $ct24 = self::$counter[24];
        $ct25 = self::$counter[25];

        $output = "
        <div role='tabpanel' class='tab-pane {$active['autologin']}' id='autologin'>
        <br>";

        # Logins vorhanden
        if ($ct10 || $ct11 || $ct12 || $ct13
            || $ct20 || $ct21 || $ct22 || $ct23 || $ct25):

        $output .= "
        <p>{$msg[327]}:</p>
        <form method='POST' class='form-horizontal'>
        ";

            # eigene Logins
            if ($ct10 || $ct11 || $ct12 || $ct13):

        $output .= <<<EOT
        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log11' name='logout' value='11' autocomplete='off' />
        <label for='log11'>{$msg[328]} ({$ct11}x)</label></div>

        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log10' name='logout' value='10' autocomplete='off' />
        <label for='log10'>{$msg[329]} ({$ct10}x)<label></div>

        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log12' name='logout' value='12' autocomplete='off' />
        <label for='log12'>{$msg[330]} ({$ct12}x)<label></div>

        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log13' name='logout' value='13' autocomplete='off' />
        <label for='log13'>{$msg[331]} ({$ct13}x)<label></div>
        EOT;

            endif;

            # andere Logins
            if ($ct20 || $ct21 || $ct22 || $ct23 || $ct25):

        $output .= <<<EOT
        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log21' name='logout' value='21' autocomplete='off' />
        <label for='log21'><hr>{$msg[332]} ({$ct21}x)</label></div>

        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log20' name='logout' value='20' autocomplete='off' />
        <label for='log20'>{$msg[333]} ({$ct20}x)</label></div>

        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log22' name='logout' value='22' autocomplete='off' />
        <label for='log22'>{$msg[334]} ({$ct22}x)</label></div>

        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log23' name='logout' value='23' autocomplete='off' />
        <label for='log23'>{$msg[335]} ({$ct23}x)</label></div>

        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log24' name='logout' value='24' autocomplete='off' />
        <label for='log24'>{$msg[336]} ({$ct24}x)</label></div>

        <div class='col-sm-offset-2 col-sm-10'>
        <input type='radio' id='log25' name='logout' value='25' autocomplete='off' />
        <label for='log25'>{$msg[337]} ({$ct25}x)</label></div>
        EOT;

            endif;  // andere Logins

        $output .= <<<EOT
        <div class='col-sm-offset-2 col-sm-10'>
        <div><br>
        <button formaction='?save=autologin&tab=autologin'
            class='btn btn-primary' type='submit'>{$msg[338]}</button>
        </div></div>
        </form>
        EOT;

        # keine Logins vorhanden
        else:
            $output .= "<p>{$msg[339]}</p>";
        endif;

        $output .= "</div>";

        return $output;
    }


    private static function tab_register(): string
    {
        $msg = self::MSG;
        $active = self::$active;
        $reglinks = self::$reglinks;

        $output = <<<EOT
        <div role='tabpanel' class='tab-pane {$active['regis']}' id='regis'>
        <form method='POST' style='margin-top: 30px;'>
        <button formaction='?save=make_reglink&tab=regis' type='submit'
            class='btn btn-primary btn-lg'>{$msg[340]}</button>
        </form>
        EOT;

        if ($reglinks):

        $output .= <<<EOT
        <p><br></p>
        <form action='?save=delete_reg&tab=regis' method='POST' class='form-horizontal'>
        <div class='panel panel-default'>

        <table class='table'>
        <tr>
        <th>#</th>
        <th></th>
        <th>{$msg[323]}</th>
        <th>{$msg[320]}</th>
        <th>{$msg[341]}</th>
        </tr>
        EOT;

            $ct_reg = 0;
            $reglinks_vorhanden = false;
            foreach ($reglinks as $link):

                # wenn RegLink vorhanden
                if (count($link) > 2) {

                    $reglinks_vorhanden = true;     // genutzt bei Button Ausgabe
                    $ct_reg++;

                    $out_radio = <<<EOT
                    <input type='radio' id='{$ct_reg}' name='regchoise' value='{$ct_reg}' autocomplete='off' />
                    <label for='reg{$ct_reg}'></label>
                    EOT;

                    $endtime = date('d.m.Y H:i', $link['pwcode_endtime']);
                    $out_mail = str_replace("_dummy_", "", $link['email']);
                    $out_link = "<<a href='{$link['notiz']}' target='blank'> {$msg[342]} </a>>";
                }

                # keine RegLink vorhanden
                else {
                    $out_radio = $endtime = $out_mail = $link['notiz'] = $out_link = "";
                    $reglinks_vorhanden = false;
                };

        $output .= <<<EOT
        <tr>
        <td>{$ct_reg}</td>
        <td>{$out_radio}</td>
        <td>{$endtime}</td>
        <td>{$out_mail}</td>
        <td>{$out_link}</td>
        </tr>
        EOT;

            endforeach;

            $output .= "</table></div>";

            if ($reglinks_vorhanden):

        $output .= <<<EOT
        <button formaction='?save=show_mail&tab=regis' class='btn btn-primary'
            type='' value='' name=''>{$msg[343]}</button>&nbsp;&emsp;&emsp;

        <button class='btn btn-primary'
            type='submit'>{$msg[344]}</button>&nbsp;&emsp;&emsp;
        EOT;

                if ($ct_reg > 1):
        $output .= <<<EOT
        <button formaction='?save=delete_allregs&tab=regis'
            class='btn Xbtn-primary' type='' value='' name=''>{$msg[345]}</button>
        EOT;
                endif;  // $ct_reg > 1

                $output .= "</form>";

            endif;  // $reglinks_vorhanden

        endif;  // $reglinks

        $output .= "</div>";

        return $output;
    }


    private static function tab_sonstiges(): string
    {
        $msg = self::MSG;
        $active = self::$active;

    $output = <<<EOT
    <div role='tabpanel' class='tab-pane {$active['sonst']}' id='sonst'>
    <p></p>

    <h3>{$msg[346]}</h3>
    <h4>{$msg[347]}</h4>
    <p><a href='https://wiki.selfhtml.org/wiki/ClientWidth'>Element.clientWidth</a>:
        <span id='clientW'></span>px</p>
    <p><a href='https://wiki.selfhtml.org/wiki/InnerWidth'>Window.innerWidth</a>:
        <span id='innerW'></span>px</p>
    <p><a href='https://wiki.selfhtml.org/wiki/OuterWidth'>Window.outerWidth</a>:
        <span id='outerW'></span>px</p>
    <h4>{$msg[348]}</h4>
    <p><a href='https://wiki.selfhtml.org/wiki/ClientHeight'>Element.clientHeight</a>:
        <span id='clientH'></span>px</p>
    <p><a href='https://wiki.selfhtml.org/wiki/InnerHeight'>Window.innerHeight</a>:
        <span id='innerH'></span>px</p>
    <p><a href='https://wiki.selfhtml.org/wiki/OuterHeight'>Window.outerHeight</a>:
        <span id='outerH'></span>px</p>
    <h3>{$msg[349]}</h3>
    <h4>{$msg[347]}</h4>
    <p><a href='https://wiki.selfhtml.org/wiki/JavaScript/Screen/width'>Screen.width</a>:
        <span id='screenW'></span>px</p>
    <p><a href='https://wiki.selfhtml.org/wiki/availWidth'>Screen.availWidth</a>:
        <span id='availW'></span>px</p>
    <h4>{$msg[348]}</h4>
    <p><a href='https://wiki.selfhtml.org/wiki/JavaScript/Screen/height'>Screen.height</a>:
        <span id='screenH'></span>px</p>
    <p><a href='https://wiki.selfhtml.org/wiki/availHeight'>Screen.availHeight</a>:
        <span id='availH'></span>px</p>
    <!--
    <p><a href='https://wiki.selfhtml.org/wiki/JavaScript/Window/matchMedia'>matchMedia</a>:
        <span id='matcMedia'></span></p> -->

    </div>
    EOT;

    $output .= <<<EOT
    <script>
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('resize', messen);
    messen();

    function messen() {
        document.getElementById('clientW')
            .textContent = document.querySelector('html')
            .clientWidth;
        document.getElementById('innerW')
            .textContent = window.innerWidth;
        document.getElementById('outerW')
            .textContent = window.outerWidth;
        document.getElementById('clientH')
            .textContent = document.querySelector('html')
            .clientHeight;
        document.getElementById('innerH')
            .textContent = window.innerHeight;
        document.getElementById('outerH')
            .textContent = window.outerHeight;
        document.getElementById('screenW')
            .textContent = screen.width;
        document.getElementById('availW')
            .textContent = screen.availWidth;
        document.getElementById('screenH')
            .textContent = screen.height;
        document.getElementById('availH')
            .textContent = screen.availHeight;

        document.getElementById('matchMedia')
            .textContent = window.matchMedia().media;
    }
    });
    </script>
    EOT;

        return $output;
    }


    private static function tab_tools(): string
    {
        $msg = self::MSG;
        $active = self::$active;

    return <<<EOT
    <div role='tabpanel' class='tab-pane {$active['tools']}' id='tools'>
    <p><br></p>

    <form method='POST'>
    <table style='border-collapse: separate; border-spacing: 10px 5px;'>
    <tr><td>
    <button formaction='../tools/lokal1.php' class='btn btn-primary'
    type='' value='' name=''>{$msg[350]}</button>&emsp;&emsp;
    </td><td>{$msg[351]}</td></tr>

    <tr><td>
    <button formaction='../tools/lokal2.php' class='btn btn-primary'
    type='' value='' name=''>{$msg[352]}</button>&emsp;&emsp;
    </td><td>{$msg[353]}</td></tr>

    <tr><td>
    <button formaction='../tools/lokal3.php' class='btn btn-primary'
    type='' value='' name=''>{$msg[354]}</button>&emsp;&emsp;
    </td><td>{$msg[355]}</td></tr>

    <tr><td>
    <button formaction='../tools/lokal4.php' class='btn btn-primary'
    type='' value='' name=''>{$msg[356]}</button>&emsp;&emsp;
    </td><td>{$msg[357]}</td></tr>

    <tr><td>
    <button formaction='../tools/maillog_show' class='btn btn-primary'
    type='' value='' name=''>{$msg[358]}</button>&emsp;&emsp;
    </td><td>{$msg[359]}</td></tr>

    <tr><td>
    <button formaction='../tools/excel_down' class='btn btn-primary'
    type='' value='' name=''>{$msg[360]}</button>&emsp;&emsp;
    </td><td>{$msg[361]}</td></tr>

    <tr><td>
    <button formaction='../tools/pdf_down' class='btn btn-primary'
    type='' value='' name=''>{$msg[362]}</button>&emsp;&emsp;
    </td><td>{$msg[363]}</td></tr>

    <tr><td>
    <button formaction='../tools/printview.php?thema=100' class='btn btn-primary'
    type='' value='' name=''>{$msg[364]}</button>&emsp;&emsp;
    </td><td>{$msg[365]}</td></tr>

    <tr><td>
    <button formaction='../tools/deletes.php' class='btn btn-primary'
    type='' value='' name=''>{$msg[366]}</button>&emsp;&emsp;
    </td><td>{$msg[367]}</td></tr>

    </table>
    </form>
    EOT;
    }



    /**
     * Java Script zur Steuerung der Tab-Navigation
     * --> ans Ende der Webseite hängen
     */
    public static function lastScriptAusgeben(): void
    {
    echo <<<EOT
    <script>
    // href mit ?Parameter und #Sprungmarke
    // -> class = 'anchor_extended'
    //
    window.addEventListener('load', function () {

    // Falls der Browser nicht automatisch zum gewünschten Element springt
    // erledigt das Javascript.
    if (window.location.hash)
        window.location.href = window.location.hash;

    // Die Steuerelemente, welche den Mechanismus auslösen sollen, werden selektiert,
    // sie müssen via class='anchor_extended' ausgezeichnet werden.
    var anchors = document.getElementsByClassName('anchor_extended');

    for (var i = 0; i < anchors.length; i++) {
        anchors[i].addEventListener('click', function (event) {
            // Prevent the anchor to perform its href-jump.
            event.preventDefault();

            // Variablen vordefinieren.
            var target = {},
            current = {}
            path = window.location.origin;

            // URL und Hash des Ziels extrahieren. Unterschieden wird zwischen a-Tag's dessen href
            // ausgelesen wird und anderen Elementen (wie z.B. div), bei denen auf das data-href=''-Attribut
            // zugegriffen wird. Für den 2. Fall benötigen wir die eben definierte path-Variable
            // welche den absoluten Pfad enthält.
            target.href = this.href
                ? this.href.split('#')
                : (path + this.dataset.href).split('#');
            target.url = target.href.length > 2
                ? target.href.slice(0, -1).join('#')
                : target.href[0];
            target.hash = target.href.length > 1
                ? target.href[target.href.length - 1]
                : '';

            // URL und Hash der aktuellen Datei.
            current.url = window.location.href.split('#').slice(0, -1).join('#');
            current.hash = window.location.hash;

            if (current.url == target.url) {
                if (current.hash == target.hash) {
                    // Dateiname und Hash sind identisch, die Seite
                    // wird lediglich neu geladen.
                    window.location.reload();
                }
                else {
                    // Der Hash unterscheidet sich, dem location-Objekt
                    // wird dieser zugeteilt, anschließend wird die Seite
                    // neu geladen.
                    window.location.hash = target.hash;
                    window.location.reload();
                };
            }
            else {
                // Der Dateiname unterscheidet sich, _GET-Daten wurden geändert
                // oder eine andere Datei soll aufgerufen werden, es wird lediglich
                // auf diese Datei verwiesen.
                window.location.href = this.href;
            };
        });
    }

    });
    </script>
    EOT;
    }
}


// EOF