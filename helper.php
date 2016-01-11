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

    /**
     * checks if an id is within one of the namespaces in $namespace_list
     *
     * @param string $namespace_list
     * @param string $id
     *
     * @return bool
     */
    function in_namespace($namespace_list, $id) {
        // PHP apparantly does not have closures -
        // so we will parse $valid ourselves. Wasteful.
        $namespace_list = preg_split('/\s+/', $namespace_list);
        //if(count($valid) == 0) { return true; }//whole wiki matches
        if((count($namespace_list)==1) and ($namespace_list[0]=="")) { return true; }//whole wiki matches
        $id = trim($id, ':');
        $id = explode(':', $id);

        // Check against all possible namespaces
        foreach($namespace_list as $namespace) {
            $namespace = explode(':', $namespace);
            $current_ns_depth = 0;
            $total_ns_depth = count($namespace);
            $matching = true;

            // Check each element, untill all elements of $v satisfied
            while($current_ns_depth < $total_ns_depth) {
                if($namespace[$current_ns_depth] != $id[$current_ns_depth]) {
                    // not a match
                    $matching = false;
                    break;
                }
                $current_ns_depth += 1;
            }
            if($matching) { return true; } // a match
        }
        return false;
    }

    /**
     * check if given $dir contains a valid namespace or is contained in a valid namespace
     *
     * @param $valid_namespaces_list
     * @param $dir
     *
     * @return bool
     */
    function is_dir_valid($valid_namespaces_list, $dir) {
        $valid_namespaces_list = preg_split('/\s+/', $valid_namespaces_list);
        //if(count($valid) == 0) { return true; }//whole wiki matches
        if((count($valid_namespaces_list)==1) && ($valid_namespaces_list[0]=="")) { return true; }//whole wiki matches
        $dir = trim($dir, ':');
        $dir = explode(':', $dir);

        // Check against all possible namespaces
        foreach($valid_namespaces_list as $valid_namespace) {
            $valid_namespace = explode(':', $valid_namespace);
            $current_depth = 0;
            $dir_depth = count($dir); //this is what is different from above!
            $matching = true;

            // Check each element, untill all elements of $v satisfied
            while($current_depth < $dir_depth) {
                if (empty($valid_namespace[$current_depth])) {
                    break;
                }
                if($valid_namespace[$current_depth] != $dir[$current_depth]) {
                    // not a match
                    $matching = false;
                    break;
                }
                $current_depth += 1;
            }
            if($matching) { return true; } // a match
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
        if (!is_array($approvals)) {
            return array();
        }
        return $approvals;
    }

    function getMeta($id = null) {
        global $ID;
        global $INFO;

        if ($id === null) $id = $ID;

        if($ID === $id && $INFO['meta']) {
            $meta = $INFO['meta'];
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

    function getSortedApprovedRevisions($id = null) {
        if ($id === null) {
            global $ID;
            $id = $ID;
        }

        static $sortedApprovedRevisions = array();
        if (!isset($sortedApprovedRevisions[$id])) {
            $approvals = $this->getApprovals($id);
            krsort($approvals);
            $sortedApprovedRevisions[$id] = $approvals;
        }

        return $sortedApprovedRevisions[$id];
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

    function getLatestApprovedRevision($id = null) {
        $approvals = $this->getSortedApprovedRevisions($id);
        foreach ($approvals as $revision => $ignored) {
            if ($this->isRevisionApproved($revision, $id)) {
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

    function isHidden($id = null) {
        if (!$this->getConf('hide drafts')) {
            return false;
        }

        // needs to check if the actual namespace belongs to the apr_namespaces
        if ($id == null) {
            global $ID;
            $id = $ID;
        }
        if (!$this->isActive($id)) {
            return false;
        }

        if ($this->getLatestApprovedRevision($id)) {
            return false;
        }
        return true;
    }

    function isHiddenForUser($id = null) {
        if (!$this->isHidden($id)) {
            return false;
        }

        if ($id == null) {
            global $ID;
            $id = $ID;
        }

        $allowedGroups = array_filter(explode(' ', trim($this->getConf('author groups'))));
        if (empty($allowedGroups)) {
            return auth_quickaclcheck($id) < AUTH_EDIT;
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

    function isActive($id = null) {
        if ($id == null) {
            global $ID;
            $id = $ID;
        }
        if (!$this->in_namespace($this->getConf('apr_namespaces'), $id)) {
            return false;
        }

        $no_apr_namespaces = $this->getConf('no_apr_namespaces');
        if (!empty($no_apr_namespaces)) {
            if ($this->in_namespace($no_apr_namespaces, $id)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Create absolute diff-link between the two given revisions
     *
     * @param string $id
     * @param int $rev1
     * @param int $rev2
     * @return string Diff-Link or empty string if $rev1 == $rev2
     */
    public function getDifflink($id, $rev1, $rev2) {
        if($rev1 == $rev2) {
            return '';
        }
        $params = 'do=diff,rev2[0]=' . $rev1 . ',rev2[1]=' . $rev2 . ',difftype=sidebyside';
        $difflink = wl($id, $params,true,'&');
        return $difflink;
    }

    function getPagesFromNamespace($namespace) {
        global $conf;
        $dir = $conf['datadir'] . '/' . str_replace(':', '/', $namespace);
        $pages = array();
        search($pages, $dir, array($this,'_search_helper'), array($namespace, $this->getConf('apr_namespaces'),
                                                                  $this->getConf('no_apr_namespaces')));
        return $pages;
    }

    /**
     * search callback function
     *
     * filter out pages which can't be approved by the current user
     * then check if they need approving
     */
    function _search_helper(&$data, $base, $file, $type, $lvl, $opts) {
        $ns = $opts[0];
        $valid_ns = $opts[1];
        $invalid_ns = $opts[2];

        if ($type == 'd') {
            return $this->is_dir_valid($valid_ns, $ns . ':' . str_replace('/', ':', $file));
        }

        if (!preg_match('#\.txt$#', $file)) {
            return false;
        }

        $id = pathID($ns . $file);
        if (!empty($valid_ns) && !$this->in_namespace($valid_ns, $id)) {
            return false;
        }

        if (!empty($invalid_ns) && $this->in_namespace($invalid_ns, $id)) {
            return false;
        }

        if (auth_quickaclcheck($id) < AUTH_DELETE) {
            return false;
        }

        $meta = $this->getMeta($id);
        if ($this->isCurrentRevisionApproved($id)) {

            // Already approved
            return false;
        }

        $data[] = array($id, $meta['approval'], $meta['last_change']['date']);
        return false;
    }

    public function removeSubnamespacePages ($pages, $namespace) {
        $cleanpages = array();
        foreach ($pages as $page) {
            if (getNS($page[0]) == $namespace) {
                $cleanpages[] = $page;
            }
        }
        return $cleanpages;
    }

}
