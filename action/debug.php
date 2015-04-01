<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_debug extends DokuWiki_Action_Plugin {

    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(Doku_Event_Handler $controller) {
        global $conf;
        if ($conf['allowdebug']) {
            $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'debug', array());
        }
    }

    function debug(&$event, $param) {
        global $ID;
        ptln('<h1>Publish plug-in debug</h1>');
        ptln('<h1>Metadata</h1>');
        ptln('<pre>');
        ptln(print_r(p_get_metadata($ID), true));
        ptln('</pre>');
        ptln('<h1>pageinfo</h1>');
        ptln('<pre>');
        ptln(print_r(pageinfo(), true));
        ptln('</pre>');
    }

}
