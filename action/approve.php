<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_approve extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $helper;

    function __construct() {
        $this->helper = plugin_load('helper', 'publish');
    }

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_io_write', array());
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'approveNS', array());
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'AFTER', $this, 'self_approve', array(), -1000);
    }

    function approveNS(Doku_Event &$event, $param) {
        if ($event->data !== 'plugin_publish_approveNS') {
            return;
        }
        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        //e.g. access additional request variables
        global $INPUT; //available since release 2012-10-13 "Adora Belle"
        $namespace = $INPUT->str('namespace');
        $pages = $this->helper->getPagesFromNamespace($namespace);
        $pages = $this->helper->removeSubnamespacePages($pages, $namespace);

        global $ID, $INFO;
        $original_id = $ID;
        foreach ($pages as $page) {
            $ID = $page[0];
            $INFO = pageinfo();
            if (!$this->helper->canApprove()) {
                continue;
            }
            $this->addApproval();
        }
        $ID = $original_id;
    }

    function handle_io_write(Doku_Event &$event, $param) {
        # This is the only hook I could find which runs on save,
        # but late enough to have lastmod set (ACTION_ACT_PREPROCESS
        # is too early)
        global $ACT;
        global $INPUT;
        global $ID;

        if ($ACT != 'show') {
            return;
        }

        if (!$INPUT->has('publish_approve')) {
            return;
        }

        if (!$this->helper->canApprove()) {
            msg($this->getLang('wrong permissions to approve'), -1);
            return;
        }

        $this->addApproval();
        send_redirect(wl($ID, array('rev' => $this->helper->getRevision()), true, '&'));
    }

    function addApproval($approvalRevision = null) {
        global $USERINFO;
        global $ID;
        global $INFO;
        global $REV;

        if($approvalRevision){
            if ($approvalRevision !== $REV && !page_exists($ID, $approvalRevision)) {
                msg($this->getLang('cannot approve a non-existing revision'), -1);
                return;
            }
        }else{
            $approvalRevision = $this->helper->getRevision();
            if (!$INFO['exists']) {
                msg($this->getLang('cannot approve a non-existing revision'), -1);
                return;
            }
        }

        $approvals = $this->helper->getApprovals();

        if (!isset($approvals[$approvalRevision])) {
            $approvals[$approvalRevision] = array();
        }

        $approvals[$approvalRevision][$INFO['client']] = array(
            $INFO['client'],
            $_SERVER['REMOTE_USER'],
            $USERINFO['mail'],
            time()
        );

        $success = p_set_metadata($ID, array('approval' => $approvals), true, true);
        if ($success) {
            msg($this->getLang('version approved'), 1);

            $data = array();
            $data['rev'] = $approvalRevision;
            $data['id'] = $ID;
            $data['approver'] = $_SERVER['REMOTE_USER'];
            $data['approver_info'] = $USERINFO;
            if ($this->getConf('send_mail_on_approve') && $this->helper->isRevisionApproved($approvalRevision)) {
                /** @var action_plugin_publish_mail $mail */
                $mail = plugin_load('action','publish_mail');
                $mail->send_approve_mail();
            }
            trigger_event('PLUGIN_PUBLISH_APPROVE', $data);
        } else {
            msg($this->getLang('cannot approve error'), -1);
        }

    }

    function self_approve(Doku_Event &$event, $param){
        global $ID;
        global $ACT;
        global $INFO;
        global $conf;
        $data = pageinfo();

        if ($ACT != 'save' || !$event->result) {
            return true;
        }

        // IO_WIKIPAGE_WRITE is always called twice when saving a page. This makes sure to only send the mail once.
        if (!$event->data[3]) {
            return true;
        }

        // Does the publish plugin apply to this page?
        if (!$this->helper->isActive($ID)) {
            return true;
        }

        // are we supposed to auto approve?
        if (!$this->getConf('auto_self_approve') || !$this->helper->canApprove()) {
            return true;
        }

        $this->addApproval($event->data[3]);
        return true;
    }

}
