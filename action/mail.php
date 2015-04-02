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

    // Funktion versendet eine Änderungsmail
    function send_change_mail(&$event, $param) {
        global $ID;
        global $ACT;
        global $REV;
        global $INFO;
        global $conf;
        $data = pageinfo();

        if ($ACT != 'save') {
            return true;
        }
        if (!$event->data[3]) {
            return true;
        }

        // get mail receiver
        //$receiver = 'grosse@cosmocode.de';//
        $receiver = $this->getConf('apr_mail_receiver');

        // get mail sender
        $sender = $data['userinfo']['mail'];

        if ($sender == $receiver) {
            dbglog('Mail not send. Sender and receiver are identical.');
//            return true;
        }

        if ($INFO['isadmin'] == '1') {
            dbglog('Mail not send. Sender is admin.');
//            return true;
        }

        // get mail subject
        //$timestamp = time(); //$data['meta']['date']['modified'];
        $timestamp = $data['lastmod'];
//        date_default_timezone_set("Europe/Paris");
        $datum = date("d.m.Y",$timestamp);
        $uhrzeit = date("H:i",$timestamp);
        $subject = $this->getLang('apr_mail_subject') . ': ' . $ID . ' - ' . $datum . ' ' . $uhrzeit;
        dbglog($subject);

        // get mail text
        //$body = 'apr_changemail_text';
        $body = $this->getLang('apr_changemail_text');
        $body = str_replace('@DOKUWIKIURL@', DOKU_URL, $body);
        $body = str_replace('@FULLNAME@', $data['userinfo']['name'], $body);
        $body = str_replace('@TITLE@', $conf['title'], $body);

        /** @var helper_plugin_publish $helper */
        $helper = plugin_load('helper','publish');
        $rev = $data['lastmod'];

        /**
         * todo it seems like the diff to the previous version is not that helpful after all. Check and remove.
        $changelog = new PageChangelog($ID);
        $difflinkPrev = $helper->getDifflink($ID, $changelog->getRelativeRevision($rev,-1), $rev);
        $difflinkPrev = '"' . $difflinkPrev . '"';
        $body = str_replace('@CHANGESPREV@', $difflinkPrev, $body);
        */

        $difflinkApr = $helper->getDifflink($ID, $helper->getLatestApprovedRevision($ID), $rev);
        $difflinkApr = '"' . $difflinkApr . '"';
        $body = str_replace('@CHANGES@', $difflinkApr, $body);

        $apprejlink = $this->apprejlink($ID, $data['lastmod']);
        $apprejlink = '"' . $apprejlink . '"';
        $body = str_replace('@APPREJ@', $apprejlink, $body);

        dbglog('mail_send?');
        $returnStatus = mail_send($receiver, $subject, $body, $sender);
        dbglog($returnStatus);
        dbglog($body);
        return $returnStatus;
    }

    // Funktion versendet eine approve-Mail
    public function send_approve_mail() {
        dbglog('send_approve_mail()');
        global $ID;
        global $ACT;
        global $REV;
        global $INFO;
        global $conf;
        $data = pageinfo();

        if ($ACT != 'save') {
           // return true;
        }

        // get mail receiver
        $receiver = $data['meta']['suggestfrom'];
        dbglog('$receiver: ' . $receiver);
        // get mail sender
        $sender = $data['userinfo']['mail'];
        dbglog('$sender: ' . $sender);
        // get mail subject
        $subject = $this->getLang('apr_mail_app_subject');
        dbglog('$subject: ' . $subject);
        // get mail text
        $body = $this->getLang('apr_approvemail_text');
        $body = str_replace('@DOKUWIKIURL@', DOKU_URL, $body);
        $body = str_replace('@FULLNAME@', $data['userinfo']['name'], $body);
        $body = str_replace('@TITLE@', $conf['title'], $body);

        $url = wl($ID, array('rev'=>$this->hlp->getLatestApprovedRevision($ID)), true, '&');
        $url = '"' . $url . '"';
        $body = str_replace('@URL@', $url, $body);
        dbglog('$body: ' . $body);

        return mail_send($receiver, $subject, $body, $sender);
    }

    /**
     * erzeugt den Link auf die edit-Seite
     *
     * @param $id
     * @param $rev
     * @return mixed|string
     */
    function apprejlink($id, $rev) {
        $data = pageinfo();

        $options = array(
             'rev'=> $rev,
             'do'=>'edit',
             'suggestfrom' => $data['userinfo']['mail'],
        );
        $difflink = wl($id, $options, true, '&');
//        $difflink = str_replace('//', '/', $difflink);
//        $difflink = str_replace('http:/', 'http://', $difflink);

        return $difflink;
    }

    /**
     * erzeugt den Diff-Link
     */
    function difflink($id, $rev1, $rev2) {
        $data = pageinfo();

        if ($rev1 == $rev2) {
//            return '';
        }
        $options = array(
            'do' => 'diff',
            'rev2[0]' => 'lastappr',
            'rev2[1]' => $rev2,
            'suggestfrom' => $data['userinfo']['mail'],
        );

//        $difflink = DOKU_URL . wl($id, '&rev2[]=' . $rev1 . '&rev2[]=' . $rev2 . '&do[diff]=1&suggestfrom=' . $data['userinfo']['mail'], '', false, '&');
        $difflink = wl($id, $options,true, '&');
        //$difflink = str_replace('//', '/', $difflink);
        //$difflink = str_replace('http:/', 'http://', $difflink);

        return $difflink;
    }

}
