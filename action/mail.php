<?php
/**
 * @license GNU General Public License, version 2
 */


if (!defined('DOKU_INC')) die();


/**
 * Class action_plugin_publish_mail
 *
 * @author Michael Große <grosse@cosmocode.de>
 */
class action_plugin_publish_mail extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    public function register(Doku_Event_Handler $controller) {

        $controller->register_hook('IO_WIKIPAGE_WRITE', 'AFTER', $this, 'send_change_mail', array());
    }

    /**
     * send an email to inform about a changed page
     *
     * @param $event
     * @param $param
     * @return bool false if there was an error passing the mail to the MTA
     */
    function send_change_mail(&$event, $param) {
        global $ID;
        global $ACT;
        global $INFO;
        global $conf;
        $data = pageinfo();

        if ($ACT != 'save') {
            return true;
        }

        // IO_WIKIPAGE_WRITE is always called twice when saving a page. This makes sure to only send the mail once.
        if (!$event->data[3]) {
            return true;
        }

        // Does the publish plugin apply to this page?
        if (!$this->hlp->isActive($ID)) {
            return true;
        }

        //are we supposed to send change-mails at all?
        if (!$this->getConf('send_mail_on_change')) {
            return true;
        }

        // get mail receiver
        $receiver = $this->getConf('apr_mail_receiver');

        // get mail sender
        $ReplyTo = $data['userinfo']['mail'];

        if ($ReplyTo == $receiver) {
            return true;
        }

        if ($INFO['isadmin'] == '1') {
            return true;
        }

        // get mail subject
        $timestamp = dformat($data['lastmod'], $conf['dformat']);
        $subject = $this->getLang('apr_mail_subject') . ': ' . $ID . ' - ' . $timestamp;

        $body = $this->create_mail_body('change');

        $mail = new Mailer();
        $mail->to($receiver);
        $mail->subject($subject);
        $mail->setBody($body);
        $mail->setHeader("Reply-To", $ReplyTo);
        $returnStatus = $mail->send();
        return $returnStatus;
    }

    /**
     * Create the body of mails to inform about a changed or an approved page
     *
     * @param string $action Must either be "change" or "approve"
     * @return bool|string
     * @internal param $pageinfo
     */
    public function create_mail_body($action) {
        global $ID;
        global $conf;
        $pageinfo = pageinfo();

        // get mail text
        $body = $this->getLang('mail_greeting') . "\n";
        $rev = $pageinfo['lastmod'];

        if ($action === 'change') {
            $body .= $this->getLang('mail_new_suggestiopns') . "\n\n";

            //If there is no approved revision show the diff to the revision before. Otherwise show the diff to the last approved revision.
            if($this->hlp->hasApprovals($pageinfo['meta'])) {
                $body .= $this->getLang('mail_changes_to_approved_rev') . "\n\n";
                $difflink = $this->hlp->getDifflink($ID, $this->hlp->getLatestApprovedRevision($ID), $rev);
            } else {
                $body .= $this->getLang('mail_changes_to_previous_rev') . "\n\n";
                $changelog = new PageChangelog($ID);
                $prevrev = $changelog->getRelativeRevision($rev, -1);
                $difflink = $this->hlp->getDifflink($ID, $prevrev, $rev);
            }
            $body = str_replace('@CHANGES@', $difflink, $body);
            $apprejlink = $this->revlink($ID, $rev);
            $body = str_replace('@URL@', $apprejlink, $body);
        } elseif ($action === 'approve') {
            $body .= $this->getLang('mail_approved') . "\n\n";
            $apprejlink = $this->revlink($ID, $rev);
            $body = str_replace('@URL@', $apprejlink, $body);
        } else {
            return false;
        }

        $body .= $this->getLang('mail_dw_signature');

        $body = str_replace('@DOKUWIKIURL@', DOKU_URL, $body);
        $body = str_replace('@FULLNAME@', $pageinfo['userinfo']['name'], $body);
        $body = str_replace('@TITLE@', $conf['title'], $body);

        return $body;
    }



    /**
     * Send approve-mail to editor of the now approved revision
     *
     * @return bool false if there was an error passing the mail to the MTA
     */
    public function send_approve_mail() {
        global $ID;
        global $REV;

        /** @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        $data = pageinfo();

        // get mail receiver
        if (!$REV) {
            $rev = $data['lastmod'];
        } else {
            $rev=$REV;
        }
        $changelog = new PageChangelog($ID);
        $revinfo = $changelog->getRevisionInfo($rev);
        $userinfo = $auth->getUserData($revinfo['user']);
        $receiver = $userinfo['mail'];

        // get mail sender
        $ReplyTo = $data['userinfo']['mail'];

        if ($ReplyTo == $receiver) {
            return true;
        }

        // get mail subject
        $subject = $this->getLang('apr_mail_app_subject');

        // get mail text
        $body = $this->create_mail_body('approve');

        $mail = new Mailer();
        $mail->to($receiver);
        $mail->subject($subject);
        $mail->setBody($body);
        $mail->setHeader("Reply-To", $ReplyTo);
        $returnStatus = $mail->send();

        return $returnStatus;
    }

    /**
     * create link to the specified revision
     *
     * @param string $id
     * @param string $rev The timestamp of the revision
     * @return string
     */
    function revlink($id, $rev) {

        $options = array(
             'rev'=> $rev,
        );
        $revlink = wl($id, $options, true, '&');

        return $revlink;
    }

}
