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

    function register(&$controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_io_write', array());
    }

    function handle_io_write(&$event, $param) {
        # This is the only hook I could find which runs on save,
        # but late enough to have lastmod set (ACTION_ACT_PREPROCESS
        # is too early)
        global $ACT;

        if ($ACT != 'show') {
            return;
        }

        if (!isset($_REQUEST['publish_approve'])) {
            return;
        }

        if (!$this->helper->canApprove()) {
            msg($this->getLang('wrong permissions to approve'), -1);
            return;
        }

        $this->addApproval();
        return;
    }

    function addApproval() {
        global $USERINFO;
        global $ID;
        global $INFO;
        global $REV;

        if (!$INFO['exists']) {
            msg($this->getLang('cannot approve a non-existing revision'), -1);
            return;
        }

        $approvalRevision = $this->helper->getRevision();
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
        } else {
            msg($this->getLang('cannot approve error'), -1);
        }

        send_redirect(wl($ID, array('rev' => $this->helper->getRevision()), true, '&'));
    }

}
