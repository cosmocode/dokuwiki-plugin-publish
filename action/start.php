<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_start extends DokuWiki_Action_Plugin {

    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(&$controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handle_start', array());
    }

    function handle_start(&$event, $param) {
        global $ACT;
        global $REV;
        global $INFO;
        global $ID;

        if($ACT != 'show') { return; }

        if($REV != '') { return; }

        # apply to readers only
        if($INFO['perm'] != AUTH_READ) { return; }

        # Check for override token
        global $_GET;
        if($_GET['force_rev']) { return; }

        # Only apply to appropriate namespaces
        if(!$this->hlp->in_namespace($this->getConf('apr_namespaces'), $ID)) { return; }

        # Find latest rev
        $meta = p_get_metadata($ID);
        if($meta['approval'][$meta['last_change']['date']]) { return; } //REV=0 *is* approved

        if(!$meta['approval']) { return; } //no approvals

        # Get list of approvals
        $all = array_keys($meta['approval']);
        if(count($all) == 0) { return; } //no approvals

        $REV = $all[count($all)-1];
    }

}
