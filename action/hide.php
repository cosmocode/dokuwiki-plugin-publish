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

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'hide', array());
        $controller->register_hook('PAGEUTILS_ID_HIDEPAGE', 'BEFORE', $this, 'hidePage', array());
    }

    /**
     * @param Doku_Event $event
     * @param array $param
     */
    function hide(Doku_Event &$event, $param) {

        if (!$this->hlp->isActive()) {
            return;
        }

        if (!$this->hlp->isHiddenForUser()) {
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

    function hidePage(Doku_Event &$event, $params) {
        if (!$this->hlp->isHiddenForUser($event->data['id'])) {
            return;
        }

        $event->data['hidden'] = true;
    }

}
