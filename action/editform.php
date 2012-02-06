<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_editform extends DokuWiki_Action_Plugin {

    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(&$controller) {
        $controller->register_hook('HTML_EDITFORM_OUTPUT', 'BEFORE', $this, 'handle_html_editform_output', array());
    }

    function handle_html_editform_output(&$event, $param) {
        global $ID;
        global $INFO;

        if (!$this->hlp->canApprove()) {
            return;
        }

        $html = '<label class="nowrap" for="approved"><input type="checkbox" id="approved" name="approved" value="1" tabindex=3 onclick="{ return approval_checkbox(\'' . $this->getConf('apr_approved_text') . '\'); }"/> <span>' . $this->getLang('apr_do_approve') . '</span></label>';
        $event->data->insertElement(12,$html); //FIXME hardcoded element position
    }
}
