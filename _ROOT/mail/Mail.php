<?php
namespace Dzg;

require __DIR__.'/Mailcfg.php';


/***********************
 * Summary of Mail
 */
class Mail
{
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

}
