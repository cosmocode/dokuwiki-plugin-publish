<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_hide extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(Doku_Event_Handler &$controller) {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'hide', array());
    }

    /**
     * @param Doku_Event $event
     * @param array $param
     */
    function hide(&$event, $param) {
        if (!$this->hlp->isHidden()) {
            return;
        }

        global $ACT;
        if (!in_array($ACT, array('show', 'edit', 'source', 'diff'))) {
            return;
        }

        $ACT = 'denied';

        $event->preventDefault();
        $event->stopPropagation();

        print p_locale_xhtml('denied');

    }

}