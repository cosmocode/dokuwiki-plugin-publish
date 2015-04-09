<?php

if(!defined('DOKU_INC')) die();

class action_plugin_publish_recent extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_publish
     */
    private $hlp;

    function __construct() {
        $this->hlp = plugin_load('helper','publish');
    }

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('HTML_RECENTFORM_OUTPUT', 'BEFORE', $this, 'handle_recent', array());
    }

    function handle_recent(Doku_Event &$event, $param) {
        if (!$this->hlp->isActive()) {
            return;
        }

        $render = $event->data->_content;

        $parent = null;
        foreach ($render as $id => $element) {

            if ($this->isParentTag($element)) {
                $parent = $id;
                continue;
            }

            if ($parent === null) {
                continue;
            }

            $id = $this->getPageId($element);
            if (!$id) {
                continue;
            }

            if ($this->hlp->isCurrentRevisionApproved($id)) {
                $event->data->_content[$parent]['class'] .= ' approved_revision';
            } else {
                $event->data->_content[$parent]['class'] .= ' unapproved_revision';
            }
            $parent = null;
        }
        return true;
    }

    function isParentTag($tag) {
        if (!isset($tag['_elem']) || $tag['_elem'] !== 'opentag') {
            return false;
        }

        if (!isset($tag['_tag']) || $tag['_tag'] !== 'div') {
            return false;
        }

        return (isset($tag['class']) && $tag['class'] === 'li');
    }

    function getPageId($tag) {
        if (!is_string($tag)) {
            return false;
        }

        $match = array();
        if (!preg_match('/<a href=".*" class="wikilink1" title="(.*)">.*/i', $tag, $match)) {
            return false;
        }

        return $match[1];
    }

}
