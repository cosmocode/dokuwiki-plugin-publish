<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_start extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handle_start', array());
    }

    function handle_start(&$event, $param) {
        global $ACT;
        global $REV;
        global $INFO;
        global $ID;

        if ($ACT !== 'show') {
            return;
        }

        if ($REV != '') {
            return;
        }

        if ($INFO['perm'] != AUTH_READ) {
            return;
        }

        global $_GET;
        if($_GET['force_rev']) {
            return;
        }

        if (!$this->hlp->isActive()) {
            return;
        }

        if (!$this->hlp->isCurrentRevisionApproved()) {
            $latestApproved = $this->hlp->getLatestApprovedRevision();
            if ($latestApproved) {
                $REV = $latestApproved;
                $INFO['rev'] = $latestApproved;
            }
        }
    }

}
