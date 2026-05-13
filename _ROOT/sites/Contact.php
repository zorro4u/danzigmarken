<?php
namespace Dzg;

require_once __DIR__.'/../siteform/contact.php';
require_once __DIR__.'/../siteprep/loader_default.php';

#require $_SERVER['DOCUMENT_ROOT']."/assets/vendor/autoload.php";
#use Gregwar\Captcha\PhraseBuilder;


/**
 * Kontaktformular
 */
class Contact extends ContactForm
{
    public static function show(): void
    {
        self::dataPreparation();
        self::formEvaluation();

        Header::show();
        self::show_body();
        Footer::show("contact");

        self::siteClose();
    }


    /**
     * Summary of view
     * [https://www.kontaktformular.com/]
     */
    private static function show_body(): void
    {
        /*
        $act_pth = explode('/', __DIR__);
        $pth_len = count($act_pth)-1;
        $root_dir = ['stamps', '_prepare'];
        for ($i=$pth_len; $i>0; $i--)
            if (in_array($act_pth[$i], $root_dir)) break;
        $root_pth_abs = implode('/', array_slice($act_pth, 0, $i+1));
        $root_pth_rel = '/'.implode('/', array_slice($act_pth, 3, $i+1-3));
        $root_site = $root_pth_rel.'/'.basename($_SESSION['main']);
        */

        #$rootdir="";
        #$root_site = $rootdir.'/'.basename($_SESSION['main']);


        $msg = self::MSG;
        $cfg = self::$cfg;
        $fehler  = self::$fehler;
        $captcha = self::$captcha;
        $question = self::$question[1];
        $show_form = self::$show_form;
        $input_name  = self::$input_name;
        $input_email = self::$input_email;
        $success_msg = self::$success_msg;
        $status_message = self::$status_message;
        $input_message_first = self::$input_message_first;
        $datenschutzerklaerung = self::$datenschutzerklaerung;

        $output = "<div class='container'>";
        #$output = "<div class='container main-container registration-form'>";
        $output .= $status_message;
        #echo statusmeldungAusgeben();

        $output .= "<div class='registration-form'>";
        $output .= "<h1>{$msg[310]}</h1><br>";

        // Seite anzeigen
        if ($show_form):
        $output .= <<<EOT

<form action='' method='POST' enctype='multipart/form-data' style='margin-top: 30px;'>


<script>
if (navigator.userAgent.search('Safari') >= 0 && navigator.userAgent.search('Chrome') < 0) {
   document.getElementsByTagName('BODY')[0].className += ' safari';
}
</script>


<div class='form-group'>
    <label for='inputName'>{$msg[311]}:</label>
    <input type='text' id='inputName' size='40' maxlength='50' name='name'
    value='".$input_name."' class='form-control' autocomplete='name' autofocus />
</div>

<div class='form-group'>
    <label for='inputEmail'>{$msg[312]}: <span style='color:red'>*</span></label>
    <input type='email' id='inputEmail' size='40' maxlength='100' name='email'
    value='".$input_email."' class='form-control' autocomplete='email' required />
</div>

<div class='form-group'>
    <label for='inputMessage'>{$msg[313]}: <span style='color:red'>*</span></label>
    <textarea id='inputMessage' name='message' rows='9' style='width:100%;' maxlength='500'
    spellcheck='true' class='form-control' autocomplete='off' required>".$input_message_first."
    </textarea>
</div>
<!-- ..................... -->
EOT;


/*
$output .= "
<!--
<p id=\"submitMessage\" class=\"".$buttonClass."\">".$formMessage;
    if (
        (isset($fehler['Honeypot']) && $fehler['Honeypot'] != '') ||
        (isset($fehler['Zeitsperre']) && $fehler['Zeitsperre'] != '') ||
        (isset($fehler['Klick-Check']) && $fehler['Klick-Check'] != '') ||
        (isset($fehler['Links']) && $fehler['Links'] != '') ||
        (isset($fehler['Badwordfilter']) && $fehler['Badwordfilter'] != '') ||
        (isset($fehler['Sendmail']) && $fehler['Sendmail'] != '') ||
        (isset($fehler['upload']) && $fehler['upload'] != '')):
        $output .= "

        <div class=\"row\">
        <div class=\"col-sm-8\">

        ";
        if (isset($fehler['Honeypot']) && $fehler['Honeypot'] != '') {
            $output .= $fehler['Honeypot'];
        }

        if (isset($fehler['Zeitsperre']) && $fehler['Zeitsperre'] != '') {
            $output .= $fehler['Zeitsperre'];
        }

        if (isset($fehler['Klick-Check']) && $fehler['Klick-Check'] != '') {
            $output .= $fehler['Klick-Check'];
        }
        if (isset($fehler['Links']) && $fehler['Links'] != '') {
            $output .= $fehler['Links'];
        }
        if (isset($fehler['Badwordfilter']) && $fehler['Badwordfilter'] != '') {
            $output .= $fehler['Badwordfilter'];
        }
        if (isset($fehler['Sendmail']) && $fehler['Sendmail'] != '') {
            $output .= $fehler['Sendmail'];
        }
        if (isset($fehler['upload']) && $fehler['upload'] != '') {
            $output .= $fehler['upload'];
        }
        $output .= "

        </div>
        </div>

        ";
    endif;
    $output .= "

</p>

<div class=\"row\">
<div class=\"col-sm-4

";

if (!empty($fehler['name'])) {
    $output .= " error";
}
if (isset($_POST['name']) && ''!=$_POST['name']) {
    $output .= " not-empty-field";
} else {
    $output .= "";
}
$output .= "

    \">
    <label class=\"control-label\" for=\"border-right\"><i id=\"user-icon\" class=\"fa fa-user\"></i></label>
    <input ";

    if ($cfg['HTML5_FEHLERMELDUNGEN']) {
        $output .= "required ";
    } else {
        $output .= "onchange=\"checkField(this)\" ";
    }
    $output .= "type=\"text\" name=\"name\" class=\"field\" placeholder=\"Name *\" value=\"".
        $_POST['name']."\" maxlength=\"".$zeichenlaenge['name']."\" id=\"border-right\"
        onclick=\"setActive(this);\" onfocus=\"setActive(this);\" />";
    if (!empty($fehler['name'])) {
        $output .= $fehler['name'];
    }
$output .= "

</div>
<div class=\"col-sm-4";

if (isset($_POST['name']) && ''!=$_POST['name']) {
    $output .= " not-empty-field";
} else {
    $output .= "";
}
$output .= "

    \">
    <label class=\"control-label\" for=\"border-right3\"><i id=\"user-icon\" class=\"fas fa-user\"></i></label>
    <input aria-label=\"Name\" type=\"text\" name=\"name\" class=\"field\" placeholder=\"Name\" value=\"".$_POST['name']."\" maxlength=\"".$zeichenlaenge['name']."\" id=\"border-right\" onclick=\"setActive(this);\" onfocus=\"setActive(this);\" />
</div>
<div class=\"col-sm-4";

if (!empty($fehler['email'])) {
    $output .= " error";
}
if (isset($_POST['email']) && ''!=$_POST['email']) {
     $output .= " not-empty-field";
 } else {
    $output .= "";
}
$output .= "

\">
<label class=\"control-label\" for=\"border-right2\"><i id=\"email-icon\" class=\"fa fa-envelope\"></i></label>
<input ";

if ($cfg['HTML5_FEHLERMELDUNGEN']) {
    $output .= "required ";
} else {
    $output .= "onchange=\"checkField(this)\" ";
}
$output .= "aria-label=\"E-Mail\" type=\"";
if ($cfg['HTML5_FEHLERMELDUNGEN']) {
    $output .= "email";
} else {
    $output .= "text";
}
$output .= "\" name=\"email\" class=\"field\" placeholder=\"E-Mail *\" value=\"".$_POST['email']."\" maxlength=\"".$zeichenlaenge['email']."\" id=\"border-right2\" onclick=\"setActive(this);\" onfocus=\"setActive(this);\" />";
if (!empty($fehler['email'])) {
    $output .= $fehler['email'];
}
$output .= "

</div>
</div>
<div class=\"row\">
<div class=\"col-sm-8 ";

if (!empty($fehler['nachricht'])) {
    $output .= "error";
} $output .= " ";
if (isset($_POST['message']) && ''!=$_POST['message']) {
    $output .= "not-empty-field ";
} else {
    $output .= "";
}
$output .= "

\">
<label  for=\"border-right3\" class=\"control-label textarea-label\"><i class=\"material-icons\">message</i></label>
<textarea ";

if ($cfg['HTML5_FEHLERMELDUNGEN']) {
    $output .= "required ";
} else {
    $output .= " onchange=\"checkField(this)\" ";
}
$output .= " aria-label=\"Nachricht\" name=\"message\" class=\"field\" rows=\"5\" placeholder=\"Nachricht *\" style=\"height:100%;width:100%;\" id=\"border-right3\" onclick=\"setActive(this);\" onfocus=\"setActive(this);\" >".$_POST['message']."</textarea>";

if (!empty($fehler['nachricht'])) {
    $output .= $fehler['nachricht'];
}
$output .= "
</div>
</div>
-->
";
#*/


// -------------------- DATEIUPLOAD START ----------------------
if (0 < $cfg['NUM_ATTACHMENT_FIELDS']) {
    $output .= <<<EOT
        <div class='row upload-row' style='background-position: 2.85rem center;
        -webkit-text-size-adjust:none;background-repeat: no-repeat;'>
        <div class='col-sm-8'>
            <label class='control-label' for='upload_field'><i id='fileupload-icon'
            class='fa fa-upload'></i></label>
        EOT;
    for ($i=0; $i < $cfg['NUM_ATTACHMENT_FIELDS']; $i++) {
        $output .= <<<EOT
            <input aria-label='Dateiupload' type='file' size=12 name='f[]' id='upload_field'
            style='font-size:17px;' onclick='setActive(this);' onfocus='setActive(this);' />
            EOT;
    };
    $output .= "</div></div>";
};
// -------------------- DATEIUPLOAD ENDE ----------------------


// -------------------- SPAMPROTECTION START ----------------------
if ($cfg['Honeypot']) {
    $output .= <<<EOT
    <div style='height: 2px; overflow: hidden;'>
        <label style='margin-top: 10px;'>
        {$msg[314]}</label>
        <div style='margin-top: 10px;'><input type='email' name='mail' value='' /></div>
    </div>
    EOT;
};
if ($cfg['Zeitsperre']) {
    $output .= "
    <input type='hidden' name='chkspmtm' value='".time()."' />";
};
if ($cfg['Klick-Check']) {
    $output .= "
    <input type='hidden' name='chkspmkc' value='chkspmbt' />";
};
if ($cfg['Sicherheitscode']) {
    $output .= "
        <div class='row captcha-row ";
    if (!empty($fehler['captcha'])) {
        $output .= "error_container";
    };
    $output .= "
        ' style='background-position: 2.85rem center;-webkit-text-size-adjust:none;
        background-repeat: no-repeat;'>
        <div class='col-sm-8 ";
    if (!empty($fehler['captcha'])) {
        $output .= "error";
    };
    $output .= <<<EOT
        '>
        <br />
        <label class='control-label' for='answer2'>
        <div>
        <!-- <i id='securitycode-icon' class='fa fa fa-unlock-alt'></i>&nbsp; -->
        <img aria-label='Captcha' src='/assets/inc/captcha.php' alt='{$msg[315]}'
        title='captcha code' id='captcha' />
        <a href='javascript:void(0);' title='{$msg[315]}' onclick="javascript:document.
        getElementById('captcha').src='/assets/inc/captcha.php?'+Math.random();cursor:pointer;">
        <span class='captchareload'><i style='color:grey;' class='fas fa-sync-alt'></i></span></a>
        </div></label>
        <input
        EOT;
    if ($cfg['HTML5_FEHLERMELDUNGEN']) {
        $output .= " required";
    } else {
        $output .= " onchange='checkField(this)'";
    };
    # placeholder='Sicherheitscode *'
    $output .= " aria-label='Eingabe' id='answer2' placeholder='{$msg[316]} *'
        type='text' maxlength='150'  class='field ";
    if (!empty($fehler['captcha'])) {
        $output .= "errordesignfields ";
    };
    $output .= "form-control' name='sicherheitscode' onclick='setActive(this);'
        onfocus='setActive(this);' spellcheck='false' />";
    if (!empty($fehler['captcha'])) {
        $output .= $fehler['captcha'];
    };
    $output .= "
        </div>
        </div>";
};

if ($cfg['Sicherheitsfrage']) {
    $output .= "
        <div class='row question-row ";
    if (!empty($fehler['q_id12'])) {
        $output .= "error_container";
    };
    $output .= "
        ' style='background-position: 2.85rem center;-webkit-text-size-adjust:none;
        background-repeat: no-repeat;'>
        <div class='col-sm-8 ";
    if (!empty($fehler['q_id12'])) {
        $output .= "error";
    };
    # <input type='hidden' name='question_id' value='{$question_id}' />
    $output .= "
        ' >
        <br />
        <label class='control-label' for='answer'>
        <div aria-label='Sicherheitsfrage'>
        <i id='securityquestion-icon' class='fa fa fa-unlock-alt'></i>&nbsp;
        {$msg[317]} <span style='color:red'>*</span>
        </div></label>
        <input ";
    if ($cfg['HTML5_FEHLERMELDUNGEN']) {
        $output .= " required ";
    } else {
        $output .= " onchange='checkField(this)' ";
    };
    $output .= " aria-label='Antwort' id='answer' placeholder='{$question}'
        type='text' maxlength='150' class='field ";
    if (!empty($fehler['q_id12'])) {
        $output .= "errordesignfields ";
    };
    $output .= "form-control' name='answer' onclick='setActive(this);'
        onfocus='setActive(this);' spellcheck='false' />";
    if (!empty($fehler['q_id12'])) {
        $output .= $fehler['q_id12'];
    };
    $output .= "
        </div>
        </div>";
};
// -------------------- SPAMPROTECTION ENDE ----------------------


// -------------------- MAIL-COPY START ----------------------
if (1 == $cfg['Kopie_senden']) {
    $output .= "
        <div class='row checkbox-row' style='background-position: 2.85rem center;
        -webkit-text-size-adjust:none;background-repeat: no-repeat;'>
        <div class='col-sm-8";

    if (isset($_POST['mail-copy']) && ''!=$_POST['mail-copy']) {
        $output .= " not-empty-field";
    } else {
        $output .= "";
    };
    $output .= "
        '>
        <label for='inlineCheckbox11' class='control-label'><i id='mailcopy-icon'
        class='fa fa-envelope'></i></label>
        <label class='checkbox-inline'>
        <input aria-label='E-Mail-Kopie senden' type='checkbox'
        id='inlineCheckbox11' name='mail-copy' value='1' ";

    if (isset($_POST['mail-copy']) && $_POST['mail-copy']=='1') {
        $output .= "checked='checked' ";
    };
    $output .= "
        onclick='setActive(this);' onfocus='setActive(this);' /> <div style='padding-top:4px;
        padding-bottom:2px;'><span style='line-height:27px;'>
        {$msg[318]}</span></div>
        </label>
        </div>
        </div";
};
// -------------------- MAIL-COPY ENDE ----------------------


// -------------------- DATAPROTECTION START ----------------------
if ($cfg['Datenschutz_Erklaerung']) {
    $output .= "
        <div class='row checkbox-row ";
    if (!empty($fehler['datenschutz'])) {
        $output .= "error_container";
    };
    $output .= "
        ' style='background-position: 2.85rem center;-webkit-text-size-adjust:none;
        background-repeat: no-repeat;'>
        <div class='col-sm-8 ";
    if (!empty($fehler['datenschutz'])) {
        $output .= "error";
    };
    if (isset($_POST['datenschutz']) && '' != $_POST['datenschutz']) {
        $output .= "not-empty-field ";
    } else {
        $output .= "";
    };
    $output .= "
        '>
        <label for='inlineCheckbox12' class='control-label'><i id='dataprotection-icon'
        class='fas fa-user-shield '></i></label>
        <label class='checkbox-inline'>
        <input ";
    if ($cfg['HTML5_FEHLERMELDUNGEN']) {
        $output .= " required ";
    } else {
        $output .= " onchange='checkField(this)' ";
    };
    $output .= "
        aria-label='Datenschutz' type='checkbox' id='inlineCheckbox12'
        name='datenschutz' value='akzeptiert' ";
    if ($_POST['datenschutz']=='akzeptiert') {
        $output .= " checked='checked' ";
    };
    $output .= "
        onclick='setActive(this);' onfocus='setActive(this);' /> <div style='padding-top:4px;
        padding-bottom:2px;line-height:27px;'> <a href='".$datenschutzerklaerung."'
        target='_blank'>{$msg[319]}</a> *</div>
        </label>";
    if (!empty($fehler['datenschutz'])) {
        $output .= $fehler['datenschutz'];
    };
    $output .= "
    </div>
    </div>";
};
// -------------------- DATAPROTECTION ENDE ----------------------

/*
$output .= "

<!--
<div class=\"row\" id=\"send\">
<div class=\"col-sm-4\"><br>
    <span style=\"line-height:26px;font-size:17px;\"><b>Hinweis:</b> Felder mit <span class=\"pflichtfeld\">*</span> müssen ausgefüllt werden.</span>
    <br><br><br>
    <button type=\"submit\" class=\"senden ".$buttonClass."\" name=\"kf-km\" id=\"submitButton\">
        <span class=\"label\">Nachricht senden</span>
        <svg class=\"loading-spinner\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\">
            <circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle>
            <path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path>
        </svg>
    </button>
</div>
</div>
--> ";
#*/

$output .= "
<br><br>
    <button type='submit' class='btn btn-lg btn-primary btn-block'
    name='kf-km'>{$msg[320]}</button>
</form>

<br><br><hr>

<ul>
    <span style='color:red'>*</span> {$msg[321]}
</ul>


<!-- ..................... --> ";


if ($cfg['Loading_Spinner']):
    $output .= "

    <script type='text/javascript'>
        document.addEventListener('DOMContentLoaded', () => {
            const element = document.getElementById('submitButton');
        });

        document.querySelector('.senden').addEventListener('click', function() {
            var form = document.getElementById('kontaktformular');
            if (form.checkValidity()) {
                this.classList.add('loading');
                this.style.backgroundColor = '#A6A6A6';
            } else {
                console.log('');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('kontaktformular');

            form.addEventListener('submit', function (event) {
                event.preventDefault(); // Prevent default form submission

                const submitButton = document.getElementById('submitButton');
                const submitButtonLabel = submitButton.querySelector('.label');
                const loadingSpinner = submitButton.querySelector('.loading-spinner');

                // Blende den Text aus und zeige den Spinner an
                submitButtonLabel.style.display = 'none';
                loadingSpinner.style.display = 'block';

                submitButton.disabled = true;

                // Simuliere den Ladevorgang und reiche das Formular nach einer Sekunde ein
                setTimeout(() => {
                    form.submit(); // Führe den echten Form-Submit nach 1 Sekunde durch
                }, 1000);
            });
        });
    </script>

    ";
endif;
if ($cfg['Erfolgsmeldung']):
    $output .= <<<EOT
    <script>
        // Überprüfe, ob die Klasse 'finished' aktiv ist
        var isFinished = document.querySelector('.senden').classList.contains('finished');

        // Ändere die Beschriftung entsprechend
        if (isFinished) {
            var submitButton = document.getElementById('submitButton');
            submitButton.innerHTML = '<span class="label_finished">Nachricht gesendet</span>';
        }
    </script>
    EOT;
endif;
if ($cfg['Klick-Check']):
    $output .= "

    <script type='text/javascript'>
        function chkspmkcfnk(){
            document.getElementsByName('chkspmkc')[0].value = 'chkspmhm';
        }
        document.getElementsByName('kf-km')[0].addEventListener('mouseenter', chkspmkcfnk);
        document.getElementsByName('kf-km')[0].addEventListener('touchstart', chkspmkcfnk);
    </script>

    ";
endif;
$output .= "

<script type='text/javascript'>
    // set class kontaktformular-validate for form if user wants to send the form > so the invalid-styles only appears after validation
    function setValidationStyles(){
        document.getElementById('kontaktformular').setAttribute('class', 'kontaktformular kontaktformular-validate');
    }
    document.getElementsByName('kf-km')[0].addEventListener('click', setValidationStyles);
    document.getElementById('kontaktformular').addEventListener('submit', setValidationStyles);
</script>


";
if (!$cfg['HTML5_FEHLERMELDUNGEN']):
    $output .= "

    <script type='text/javascript'>
        // set class kontaktformular-validate for form if user wants to send the form > so the invalid-styles only appears after validation
        function checkField(field){
            if (''!=field.value){

                // if field is checkbox: go to parentNode and do things because checkbox is in label-element
                if ('checkbox'==field.getAttribute('type')) {
                    field.parentNode.parentNode.classList.remove('error');
                    field.parentNode.nextElementSibling.style.display = 'none';
                }
                // field is no checkbox: do things with field
                else {
                    field.parentNode.classList.remove('error');
                    field.nextElementSibling.style.display = 'none';
                }

                // remove class error_container from parent-elements
                field.parentNode.parentNode.parentNode.classList.remove('error_container');
                field.parentNode.parentNode.classList.remove('error_container');
                field.parentNode.classList.remove('error_container');
            }
        }
    </script>

    ";
endif;
$output .= "

<script>
    // --------------------- field active / inactive

    // set active-class to field
    function setActive(element){
        // set onblur-function to set field inactive
        element.focus();
        element.setAttribute('onblur', 'setInactive(this)');

        // set active-class to parent-div
        var parentDiv = getParentDiv(element);

        // if field is security-row: go to parentNode and do things
        if (
            parentDiv.classList.contains('question-input-div') ||
            parentDiv.classList.contains('captcha-input-div')
        ){
            parentDiv.parentNode.classList.add('active-field');
        }
        // field is no security-row: do things with field
        else {
            parentDiv.classList.add('active-field');
        }

        // field is a selectBox > mark selected option
        if (element.classList.contains('select-input') && ''!=element.value){
            var selectBox = getSiblingUl(element);
            var selectBoxOptions = selectBox.childNodes;
            for (i = 0; i < selectBoxOptions.length; ++i) {
                if ('li'==selectBoxOptions[i].nodeName.toLowerCase()){
                    if (element.value==selectBoxOptions[i].innerHTML){
                        selectBoxOptions[i].classList.add('active');
                    }
                    else {
                        selectBoxOptions[i].classList.remove('active');
                    }
                }
            }
        }
    }

    // set field inactive
    function setInactive(element){

        // remove active-class from parent-div
        var parentDiv = getParentDiv(element);

        // if field is security-row: go to parentNode and do things
        if (
            parentDiv.classList.contains('question-input-div') ||
            parentDiv.classList.contains('captcha-input-div')
        ){
            parentDiv.parentNode.classList.remove('active-field');
        }
        // field is no security-row: do things with field
        else{
            parentDiv.classList.remove('active-field');
        }

        // field contains string > set not-empty-class
        if (''!=element.value){
            parentDiv.classList.add('not-empty-field');
        }
        // field doesn't contain string > remove not-empty-class
        else{
            parentDiv.classList.remove('not-empty-field');
        }
    }
    // --------------------- helper

    // get the closest parent-div
    function getParentDiv(element) {
        while (element && element.parentNode) {
            element = element.parentNode;
            if (element.tagName && 'div'==element.tagName.toLowerCase()){
                return element;
            }
        }
        return null;
    }
</script>
<!-- ..................... -->

";
elseif ($success_msg !== ""):  // positive Statusausgabe ohne Formular
    $output .= "<br><br><br>";

    /*
    $output .= "
    <!-- <hr><br>
    <div><form action=".$root_site." method=\"POST\">
    <button class=\"btn btn-lg btn-primary btn-block\" type=\"submit\">
    Startseite</button>
    </form></div> -->";
    */

endif;  // Seite anzeigen

/*
$output .= "
<!-- mit Scroll-Leiste: <iframe src=\"./kontakt3/kontakt.php\" style=\"border: none; width:100%; height:700px;\"></iframe> -->
<!-- ohne Scroll-Leiste: <iframe src=\"./kontakt3/kontakt.php\" id=\"idIframe\" onload=\"iframeLoaded()\" style=\"border: none; width:100%;\" allowfullscreenscrolling=\"no\"> </iframe> -->
<!-- <iframe src=\"./kontakt1/kontakt.temp.php\" id=\"idIframe\" onload=\"iframeLoaded()\" style=\"border: none; width:100%;\" allowfullscreenscrolling=\"no\"> </iframe> -->
";
*/

$output .= "</div>";
$output .= "</div>";


        // HTML Ausgabe
        //
        echo $output;
    }
}


// EOF