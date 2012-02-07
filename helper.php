<?php
/**
 * DokuWiki Plugin publish (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Jarrod Lowe <dokuwiki@rrod.net>
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_publish extends DokuWiki_Plugin {

    private $sortedApprovedRevisions = null;

    // FIXME find out what this is supposed to do and how it can be done better
    function in_namespace($valid, $check) {
        // PHP apparantly does not have closures -
        // so we will parse $valid ourselves. Wasteful.
        $valid = preg_split('/\s+/', $valid);
        //if(count($valid) == 0) { return true; }//whole wiki matches
        if((count($valid)==1) and ($valid[0]=="")) { return true; }//whole wiki matches
        $check = trim($check, ':');
        $check = explode(':', $check);

        // Check against all possible namespaces
        foreach($valid as $v) {
            $v = explode(':', $v);
            $n = 0;
            $c = count($v);
            $matching = 1;

            // Check each element, untill all elements of $v satisfied
            while($n < $c) {
                if($v[$n] != $check[$n]) {
                    // not a match
                    $matching = 0;
                    break;
                }
                $n += 1;
            }
            if($matching == 1) { return true; } // a match
        }
        return false;
    }

    // FIXME find out what this is supposed to do and how it can be done better
    function in_sub_namespace($valid, $check) {
        // is check a dir which contains any valid?
        // PHP apparantly does not have closures -
        // so we will parse $valid ourselves. Wasteful.
        $valid = preg_split('/\s+/', $valid);
        //if(count($valid) == 0) { return true; }//whole wiki matches
        if((count($valid)==1) and ($valid[0]=="")) { return true; }//whole wiki matches
        $check = trim($check, ':');
        $check = explode(':', $check);

        // Check against all possible namespaces
        foreach($valid as $v) {
            $v = explode(':', $v);
            $n = 0;
            $c = count($check); //this is what is different from above!
            $matching = 1;

            // Check each element, untill all elements of $v satisfied
            while($n < $c) {
                if($v[$n] != $check[$n]) {
                    // not a match
                    $matching = 0;
                    break;
                }
                $n += 1;
            }
            if($matching == 1) { return true; } // a match
        }
        return false;
    }

    function canApprove() {
        global $INFO;
        global $ID;

        if (!$this->in_namespace($this->getConf('apr_namespaces'), $ID)) {
            return false;
        }

        return ($INFO['perm'] >= AUTH_DELETE);
    }

    function getRevision() {
        global $INFO;
        if (!$INFO['rev']) {
            return $INFO['lastmod'];
        }
        return $INFO['rev'];
    }

    function getApprovals($id = null) {
        $meta = $this->getMeta($id);
        if (!isset($meta['meta']['approval'])) {
            return array();
        }
        $approvals = $meta['meta']['approval'];
        return $approvals;
    }

    function getMeta($id = null) {
        global $ID;
        if ($id === null || $ID === $id) {
            global $INFO;
            return $INFO;
        } else {
            return p_get_metadata($id);
        }
    }

    function getApprovalsOnRevision($revision) {
        $approvals = $this->getApprovals();

        if (isset($approvals[$revision])) {
            return $approvals[$revision];
        }
        return array();
    }

    function getSortedApprovedRevisions() {
        if ($this->sortedApprovedRevisions === null) {
            $approvals = $this->getApprovals();
            krsort($approvals);
            $this->sortedApprovedRevisions = $approvals;
        }
        return $this->sortedApprovedRevisions;
    }

    function isRevisionApproved($revision, $id = null) {
        $approvals = $this->getApprovals($id);
        if (!isset($approvals[$revision])) {
            return false;
        }
        return (count($approvals[$revision]) >= $this->getConf('number_of_approved'));
    }

    function isCurrentRevisionApproved() {
        return $this->isRevisionApproved($this->getRevision());
    }

    function getLatestApprovedRevision() {
        $approvals = $this->getSortedApprovedRevisions();
        foreach ($approvals as $revision => $ignored) {
            if ($this->isRevisionApproved($revision)) {
                return $revision;
            }
        }
        return 0;
    }

    function getLastestRevision() {
        global $INFO;
        return $INFO['meta']['date']['modified'];
    }

    function getApprovalDate() {
        if (!$this->isCurrentRevisionApproved()) {
            return -1;
        }

        $approvals = $this->getApprovalsOnRevision($this->getRevision());
        uasort($approvals, array(&$this, 'cmpApprovals'));
        $keys = array_keys($approvals);
        return $approvals[$keys[$this->getConf('number_of_approved') -1]][3];

    }

    function cmpApprovals($left, $right) {
        if ($left[3] == $right[3]) {
            return 0;
        }
        return ($left[3] < $right[3]) ? -1 : 1;
    }

    function getApprovers() {
        $approvers = $this->getApprovalsOnRevision($this->getRevision());
        $result = array();
        foreach ($approvers as $approver) {
            $result[] = editorinfo($this->getApproverName($approver));
        }
        return $result;
    }

    function getApproverName($approver) {
        if ($approver[1]) {
            return $approver[1];
        }
        if ($approver[2]) {
            return $approver[2];
        }
        return $approver[0];
    }

    function getPreviousApprovedRevision() {
        $currentRevision = $this->getRevision();
        $approvals = $this->getSortedApprovedRevisions();
        foreach ($approvals as $revision => $ignored) {
            if ($revision >= $currentRevision) {
                continue;
            }
            if ($this->isRevisionApproved($revision)) {
                return $revision;
            }
        }
        return 0;
    }

}
