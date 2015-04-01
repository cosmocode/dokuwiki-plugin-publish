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

//        dbglog(pageinfo());
        $difflink = $this->difflink($ID, $this->getLastApproved(), $data['lastmod']);
        $difflink = '"' . $difflink . '"';
        $body = str_replace('@CHANGES@', $difflink, $body);
        $apprejlink = $this->apprejlink($ID, $data['lastmod']);
        $apprejlink = '"' . $apprejlink . '"';
        $body = str_replace('@APPREJ@', $apprejlink, $body);

        dbglog('mail_send');
        return mail_send($receiver, $subject, $body, $sender);
    }

    function getLastApproved() {
        $data = pageinfo();
        if (!$data['meta']['approval']) {
            return '';
        }
        $allapproved = array_keys($data['meta']['approval']);
        dbglog('$allapproved: ' . $allapproved);
        rsort($allapproved);

        $latestapproved = $allapproved[0];
        dbglog('$latest_rev: ' . $latestapproved);

        return $latestapproved;
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
