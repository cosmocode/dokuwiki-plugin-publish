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

    function getRevision($id = null) {
        global $REV;
        if (isset($REV) && !empty($REV)) {
            return $REV;
        }
        $meta = $this->getMeta($id);
        if (isset($meta['last_change']['date'])) {
            return $meta['last_change']['date'];
        }
        return $meta['date']['modified'];
    }

    function getApprovals($id = null) {
        $meta = $this->getMeta($id);
        if (!isset($meta['approval'])) {
            return array();
        }
        $approvals = $meta['approval'];
        return $approvals;
    }

    function getMeta($id = null) {
        global $ID;
        if ($id === null || $ID === $id) {
            global $INFO;
            $meta = $INFO['meta'];
            $id = $ID;
        } else {
            $meta = p_get_metadata($id);
        }

        $this->checkApprovalFormat($meta, $id);

        return $meta;
    }

    function checkApprovalFormat($meta, $id) {
        if (isset($meta['approval_version']) && $meta['approval_version'] >= 2) {
            return;
        }

        if (!$this->hasApprovals($meta)) {
            return;
        }

        $approvals = $meta['approval'];
        foreach (array_keys($approvals) as $approvedId) {
            $keys = array_keys($approvals[$approvedId]);

            if (is_array($approvals[$approvedId][$keys[0]])) {
                continue; // current format
            }

            $newEntry = $approvals[$approvedId];
            if (count($newEntry) !== 3) {
                //continue; // some messed up format...
            }
            $newEntry[] = intval($approvedId); // revision is the time of page edit

            $approvals[$approvedId] = array();
            $approvals[$approvedId][$newEntry[0]] = $newEntry;
        }
        p_set_metadata($id, array('approval' => $approvals), true, true);
        p_set_metadata($id, array('approval_version' => 2), true, true);
    }

    function hasApprovals($meta) {
        return isset($meta['approval']) && !empty($meta['approval']);
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

    function isCurrentRevisionApproved($id = null) {
        return $this->isRevisionApproved($this->getRevision($id), $id);
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
        if (count($approvers) === 0) {
            return;
        }

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

    function isHidden() {
        global $ID;

        if (!$this->getConf('hide drafts')) {
            return false;
        }

        if ($this->getLatestApprovedRevision()) {
            return false;
        }
        return true;
    }

    function isHiddenForUser() {
        if (!$this->isHidden()) {
            return false;
        }

        global $ID;
        $allowedGroups = array_filter(explode(' ', trim($this->getConf('author groups'))));
        if (empty($allowedGroups)) {
            return auth_quickaclcheck($ID) < AUTH_EDIT;
        }

        if (!$_SERVER['REMOTE_USER']) {
            return true;
        }

        global $USERINFO;
        foreach ($allowedGroups as $allowedGroup) {
            $allowedGroup = trim($allowedGroup);
            if (in_array($allowedGroup, $USERINFO['grps'])) {
                return false;
            }
        }
        return true;
    }
}
