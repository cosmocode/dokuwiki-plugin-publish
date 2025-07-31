<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_handleshowrev extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('HTML_SHOWREV_OUTPUT', 'BEFORE', $this, 'handle_showrev', array());
    }

    /**
     * @param Doku_Event $event
     * @param array $param
     */
    function handle_showrev(Doku_Event &$event, $param) {
        if (!$this->hlp->isActive()) {
            return;
        }
        
        if ($this->getConf('hide_showrev')) {
            $event->preventDefault();    
        }
        
        return;
    }
}
