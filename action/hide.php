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

    function register(&$controller) {
        if ($this->getConf('hide drafts')) {
            $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'hide', array());
        }
    }

    /**
     * @param Doku_Event $event
     * @param array $param
     */
    function hide(&$event, $param) {
        global $ID;
        global $REV;
        if ($this->hlp->isRevisionApproved($REV, $ID)) {
            return;
        }

        $allowedGroups = array_filter(explode(' ', trim($this->getConf('author groups'))));
        if (empty($allowedGroups)) {
            if (auth_quickaclcheck($ID) >= AUTH_EDIT) {
                return;
            }
        } else {
            if ($_SERVER['REMOTE_USER']) {
                global $USERINFO;
                foreach ($allowedGroups as $allowedGroup) {
                    $allowedGroup = trim($allowedGroup);
                    if (in_array($allowedGroup, $USERINFO['grps'])) {
                        return;
                    }
                }
            }
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