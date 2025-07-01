<?php
namespace Dzg\Mail;

require __DIR__.'/Mailcfg.php';
require __DIR__.'/PHPMailer/PHPMailer.php';
require __DIR__.'/PHPMailer/Exception.php';
#require __DIR__.'/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/***********************
 * Summary of Mail
 */
class Mail
{
    private function __construct() {}


    /***********************
     * Summary of sendMyMail
     * prepare and send email via PHP included mail-function
     *
     * $email_send = self::sendMyMail(
                        $smtp['from_addr'],
                        $smtp['from_name'],
                        $mailto,
                        $subject,
                        $mailcontent);
     * @param mixed $fromMail
     * @param mixed $fromName
     * @param mixed $toMail
     * @param mixed $subject
     * @param mixed $content
     * @param mixed $attachments
     * @return bool
     */
    public static function sendMyMail($fromMail, $fromName, $toMail, $subject, $content, $attachments=array()): bool
    {
        $boundary = md5(uniqid(time()));
        $eol = PHP_EOL;

        // header
        $header = "From: =?UTF-8?B?".base64_encode(stripslashes($fromName))."?= <".$fromMail.">".$eol;
        $header .= "Reply-To: <".$fromMail.">".$eol;
        $header .= "MIME-Version: 1.0".$eol;
        if (is_array($attachments) && 0<count($attachments)) {
            $header .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"";
        }
        else {
            $header .= "Content-type: text/plain; charset=utf-8";
        }

        // content with attachments
        if (is_array($attachments) && 0<count($attachments)) {

            // content
            $message = "--".$boundary.$eol;
            $message .= "Content-type: text/plain; charset=utf-8".$eol;
            $message .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
            $message .= $content.$eol;

            // attachments
            foreach ($attachments as $filename=>$filecontent) {
                $filecontent = chunk_split(base64_encode($filecontent));
                $message .= "--".$boundary.$eol;
                $message .= "Content-Type: application/octet-stream; name=\"".$filename."\"".$eol;
                $message .= "Content-Transfer-Encoding: base64".$eol;
                $message .= "Content-Disposition: attachment; filename=\"".$filename."\"".$eol.$eol;
                $message .= $filecontent.$eol;
            }
            $message .= "--".$boundary."--";
        }

        // content without attachments
        else {
            $message = $content;
        }

        // subject
        $subject = "=?UTF-8?B?".base64_encode($subject)."?=";

        // send mail
        return mail($toMail, $subject, $message, $header);
    }


    /***********************
     * Summary of send
     * prepare and send email via PHPMailer Class
     *
     * if ($smtp['enabled'] !== 0)
     * $email_send = self::send(
                        $smtp['mail_host'],
                        $smtp['login_usr'],
                        $smtp['login_pwd'],
                        $smtp['encryption'],
                        $smtp['smtp_port'],
                        $smtp['from_addr'],
                        $smtp['from_name'],
                        $mailto,
                        $subject,
                        $mailcontent,
                        [],
                        'upload_directory',
                        $smtp['debug'] );
     * @param mixed $host
     * @param mixed $user
     * @param mixed $password
     * @param mixed $encryption
     * @param mixed $port
     * @param mixed $from
     * @param mixed $fromName
     * @param mixed $to
     * @param mixed $subject
     * @param mixed $body
     * @param mixed $attachments
     * @param mixed $attachmentFolder
     * @param mixed $debug
     * @return bool
     */
    public static function send($host, $user, $password, $encryption, $port, $from, $fromName, $to, $subject, $body, $attachments, $attachmentFolder, $debug = 0): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = 'utf-8';
            $mail->Encoding = 'base64';
            $mail->setLanguage('de');
            $mail->SMTPDebug = $debug;
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $password;
            $mail->SMTPSecure = $encryption;
            $mail->Port = $port;
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($from);
            foreach($attachments as $attachment) {
                $mail->addAttachment($attachmentFolder . '/' . $attachment, $attachment);
            }
            $mail->Subject = "=?UTF-8?B?".base64_encode($subject)."?=";
            $mail->Body = $body;

            $mail->send();

            return true;

        } catch(Exception $e) {
            return false;
        }
    }
}
