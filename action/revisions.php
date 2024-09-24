<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_revisions extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('HTML_REVISIONSFORM_OUTPUT', 'BEFORE', $this, 'handle_revisions', array());
    }

    function handle_revisions(Doku_Event &$event, $param) {
        global $ID;
        global $INFO;

        if (!$this->hlp->isActive()) {
            return;
        }

        $meta = p_get_metadata($ID);

        $member = null;
        for ($pos = 0; $pos < $event->data->elementCount(); $pos++) {
            $ref = $event->data->getElementAt($pos);
            if ($ref->getType() != 'tagclose') {
                if ($ref->val() == 'div' && $ref->attr('class') == 'li')
                    $member = $event->data->getElementAt($pos);

                if ($member && $ref->attr('name') == 'rev2[]'){
                    $revision = $ref->attr('value');
                    if ($revision == 'current') {
                        // handle minor revisions and external edits
                        $revision = isset($meta['last_change']['date']) ?
                            $meta['last_change']['date'] :
                            $INFO['meta']['date']['modified'];
                    }
                    if ($this->hlp->isRevisionApproved($revision)) {
                        $member->addClass('approved_revision');
                    } else {
                        $member->addClass('unapproved_revision');
                    }
                    $member = null;
                }
            }
        }
        return true;
    }

}
