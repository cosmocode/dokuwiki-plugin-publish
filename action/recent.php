<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_recent extends DokuWiki_Action_Plugin {

    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(&$controller) {
        $controller->register_hook('HTML_RECENTFORM_OUTPUT', 'BEFORE', $this, handle_recent, array());
    }


    function handle_recent(&$event, $param) {

        $member = null;
        foreach($event->data->_content as $key => $ref) {
            if ($ref['_elem'] == 'opentag' && $ref['_tag'] == 'div' && $ref['class'] == 'li') {
                $member = $key;
            }

            if ($member && $ref['_elem'] == 'opentag' &&
                $ref['_tag'] == 'a' && $ref['class'] == 'diff_link'){
                $name = $ref['href'];
                $name = explode('?', $name);
                $name = explode('&', $name[1]);
                $usename = null;

                foreach($name as $n) {
                    $fields = explode('=', $n);
                    if($fields[0] == 'id') {
                        $usename = $fields[1];
                        break;
                    }
                }

                if ($usename) {
                    if ($this->hlp->in_namespace($this->getConf('apr_namespaces'), $usename)) {
                        $meta = p_get_metadata($usename);

                        if ($meta['approval'][$meta['last_change']['date']]) {
                            $event->data->_content[$member]['class'] = 'li approved_revision';
                        } else {
                            $event->data->_content[$member]['class'] = 'li unapproved_revision';
                        }
                    }
                }
                $member = null;
            }
        }
        return true;
    }
}
