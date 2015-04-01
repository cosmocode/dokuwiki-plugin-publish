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
        $latest_rev = $meta['last_change']['date'];

        $member = null;
        foreach ($event->data->_content as $key => $ref) {
            if(isset($ref['_elem']) && $ref['_elem'] == 'opentag' && $ref['_tag'] == 'div' && $ref['class'] == 'li') {
                $member = $key;
            }

            if ($member && $ref['_elem'] == 'tag' &&
                $ref['_tag'] == 'input' && $ref['name'] == 'rev2[]'){

                $revision = $ref['value'];
                if ($revision == 'current') {
                    $revision = $INFO['meta']['date']['modified'];
                }
                if ($this->hlp->isRevisionApproved($revision)) {
                    $event->data->_content[$member]['class'] = 'li approved_revision';
                } else {
                    $event->data->_content[$member]['class'] = 'li unapproved_revision';
                }
                $member = null;
            }
        }

        return true;
    }

}
