<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_removeattic extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('PLUGIN_PUBLISH_APPROVE', 'AFTER', $this, 'remove', array());
    }

    /**
     * @param Doku_Event $event
     * @param array $param
     */
    function remove(&$event, $param) {
        if (!$this->hlp->isActive()) {
            return;
        }

        if (!$this->getConf('delete attic on first approve')) {
            return;
        }

        if ($this->hlp->getPreviousApprovedRevision()) {
            return; // previous version exist
        }
        global $ID;

        $changelog = new PageChangelog($ID, 0);
        $revisions = $changelog->getRevisions(0, 0);

        foreach ($revisions as $revision) {
            $fn = wikiFN($ID, $revision);
            if (file_exists($fn)) {
                @unlink($fn);
            }
        }
    }

}
