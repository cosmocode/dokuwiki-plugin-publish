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
        $controller->register_hook('PAGEUTILS_ID_HIDEPAGE', 'BEFORE', $this, 'hidePage', array());
    }

    /**
     * @param Doku_Event $event
     * @param array $param
     */
    function hide(Doku_Event &$event, $param) {

        // if the actual namespace is aet in the no_apr_namespace
        global $ID;
        $no_apr_namespaces = $this->getConf('no_apr_namespaces');
        if (!empty($no_apr_namespaces)) {
            if ($this->hlp->in_namespace($no_apr_namespaces, $ID)) {
                return false;
            }
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