<?php
/**
 * DokuWiki Plugin publish (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Jarrod Lowe <dokuwiki@rrod.net>
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */

// TODO:
// Old Revisions Display   X
// Recent Changes Display X
// Redirection X
// List of Unapproved Documents user has permission to approve X
// Namespace restrictions + admin X
// Diff Links in banner on Prev Approved X
// List of Recent Approvals X
// Subscriptions should show approvals - hard (MAIL_MESSAGE_SEND is the only appropriate hook)
// Allow submits of docs with no changes for approval, with autocomment X
// RSS Info -- hard (no hooks in feed.php)
// Internationalisation (or not) X

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class action_plugin_publish extends DokuWiki_Action_Plugin {
    private $hlp;
    private $written = false;     // set to true after handling IO_WIKIPAGE_WRITE

    function action_plugin_publish(){
        $this->hlp = plugin_load('helper','publish'); // load helper plugin
    }

    function register(&$controller) {
        $controller->register_hook('HTML_EDITFORM_OUTPUT', 'BEFORE', $this, handle_html_editform_output, array());
        #$controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, debug, array());
        $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, handle_display_banner, array());
        $controller->register_hook('IO_WIKIPAGE_READ', 'AFTER', $this, handle_io_read, array());
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, handle_io_write, array());
        $controller->register_hook('HTML_REVISIONSFORM_OUTPUT', 'BEFORE', $this, handle_revisions, array());
        $controller->register_hook('HTML_RECENTFORM_OUTPUT', 'BEFORE', $this, handle_recent, array());
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, handle_start, array());
    }

    function handle_html_editform_output(&$event, $param) {
        global $ID;
        global $INFO;

        if(!$this->hlp->in_namespace($this->getConf('apr_namespaces'), $ID)) {
             return;
        }
        if($INFO['perm'] < AUTH_DELETE) {
            return;
        }

        $html = '<label class="nowrap" for="approved"><input type="checkbox" id="approved" name="approved" value="1" tabindex=3 onclick="{ return approval_checkbox(\'' . $this->getConf('apr_approved_text') . '\'); }"/> <span>' . $this->getLang('apr_do_approve') . '</span></label>';
        $event->data->insertElement(12,$html); //FIXME hardcoded element position
    }

    function debug(&$event, $param) {
        global $ID;
        ptln('<pre>');
        ptln(print_r(p_get_metadata($ID), true));
        ptln(print_r(pageinfo(), true));
        ptln('</pre>');
    }

    /**
     * hack to force the wiki page to be different and ensure IO_WIKIPAGE_WRITE is fired
     */
    function handle_io_read(&$event, $param) {
        global $ID, $ACT;

        $id = ($event->data[1] ? $event->data[1].':' : '').$event->data[2];
        $rev = $event->data[3];

        if ($ACT == 'save' && $ID == $id && !$rev && !$this->written) {
            $currentApproval = p_get_metadata($ID,'approval',METADATA_DONT_RENDER);
            if ($_POST['approved'] != $currentApproval) {
                $event->result .= "%%\n%%";
            }
        }
    }

    function handle_io_write(&$event, $param) {
        # This is the only hook I could find which runs on save,
        # but late enough to have lastmod set (ACTION_ACT_PREPROCESS
        # is too early)
        global $_POST;
        global $ID;
        global $ACT;
        global $USERINFO;
        global $INFO;
        if(!$this->hlp->in_namespace($this->getConf('apr_namespaces'), $ID)) { return; }
        if($INFO['perm'] < AUTH_DELETE) { return true; }
        if($ACT != 'save') { return true; }
        if(!$event->data[3]) { return true; } # don't approve the doc being moved to archive
        if($_POST['approved']) {
            $data = pageinfo();
            #$newdata = p_get_metadata($ID, 'approval');
            $newdata = $data['meta']['approval'];
            $newdata[$data['lastmod']] = array($data['client'], $_SERVER['REMOTE_USER'], $USERINFO['mail']);
            p_set_metadata($ID, array('approval' => $newdata), true, true);
        }
        $this->written = true;
        return true;
    }

    function handle_display_banner(&$event, $param) {
        global $ID;
        global $REV;
        global $INFO;

        if(!$this->hlp->in_namespace($this->getConf('apr_namespaces'), $ID)) return;
        if($event->data != 'show') return true;
        if(!$INFO['exists']) return true;

        $strings = array();
        $meta = p_get_metadata($ID);
        $rev = $REV;
        if(!$rev) { $rev = $meta['last_change']['date']; }
        if(!$meta['approval']) { $meta['approval'] = array(); }
        $allapproved = array_keys($meta['approval']);
        sort($allapproved);
        $latest_rev = $meta['last_change']['date'];
        #$strings[] = '<!-- ' . print_r($meta, true) . '-->';

        $longdate = dformat($rev);

        # Is this document approved?
        $approver = null;
        if($meta['approval'][$rev]) {
            # Approved
            if(is_array($meta['approval'][$rev])) {
              $approver = $meta['approval'][$rev][1];
              if(!$approver) { $approver = $meta['approval'][$rev][2]; }
              if(!$approver) { $approver = $meta['approval'][$rev][0]; }
            }else{
              $approver = $meta['approval'][$rev];
            }
        }

        # What is the most recent approved version?
        $most_recent_approved = null;
        $id = count($allapproved)-1;
        if($id >= 0) {
            if($allapproved[$id] > $rev) {
                $most_recent_approved = $allapproved[$id];
            }
        }

        # Latest, if draft
        $most_recent_draft = null;
        #$strings[] = '<!-- lr='.$latest_rev.', r='.$rev.', mra='.$most_recently_approved.', d='.($latest_rev != $rev).','.($latest_rev != $most_recently_approved).' -->';
        if($latest_rev != $rev && $latest_rev != $most_recent_approved) {
            $most_recent_draft = $latest_rev;
        }

        # Approved *before* this one
        $previous_approved = null;
        foreach($allapproved as $arev) {
            if($arev >= $rev) { break; }
            $previous_approved = $arev;
        }

        $strings[] = '<div class="approval approved_';
        if($approver && !$most_recent_approved) { $strings[] = 'yes'; } else { $strings[] = 'no'; }
        $strings[] = '">';

        if($most_recent_draft) {
            $strings[] = '<span class="approval_latest_draft">';
            $strings[] = sprintf($this->getLang('apr_recent_draft'), wl($ID, 'force_rev=1'));
            $strings[] = $this->difflink($ID, null, $REV) . '</span>';
        }

        if($most_recent_approved) {
            # Approved, but there is a more recent version
            $userrev = $most_recent_approved;
            if($userrev == $latest_rev) { $userrev = ''; }
            $strings[] = '<span class="approval_outdated">';
            $strings[] = sprintf($this->getLang('apr_outdated'), wl($ID, 'rev=' . $userrev));
            $strings[] = $this->difflink($ID, $userrev, $REV) . '</span>';
        }

        if(!$approver) {
            # Draft
            $strings[] = '<span class="approval_draft">';
            $strings[] = sprintf($this->getLang('apr_draft'),
                            '<span class="approval_date">' . $longdate . '</span>');
            $strings[] = '</span>';
        }

        if($approver) {
            # Approved
            $strings[] = '<span class="approval_approved">';
            $strings[] = sprintf($this->getLang('apr_approved'),
                            '<span class="approval_date">' . $longdate . '</span>',
                            editorinfo($approver));
            $strings[] = '</span>';
        }

        if($previous_approved) {
            $strings[] = '<span class="approval_previous">';
            $strings[] = sprintf($this->getLang('apr_previous'),
                            wl($ID, 'rev=' . $previous_approved),
                            dformat($previous_approved));
            $strings[] = $this->difflink($ID, $previous_approved, $REV) . '</span>';
        }

        $strings[] = '</div>';

        ptln(implode($strings));
        return true;
    }

    function handle_revisions(&$event, $param) {
        global $ID;
        global $REV;
        if(!$this->hlp->in_namespace($this->getConf('apr_namespaces'), $ID)) { return; }
        $meta = p_get_metadata($ID);
        $latest_rev = $meta['last_change']['date'];

        $member = null;
        foreach($event->data->_content as $key => $ref) {
            if($ref['_elem'] == 'opentag' && $ref['_tag'] == 'div' && $ref['class'] == 'li') {
                $member = $key;
            }

            if($member && $ref['_elem'] == 'tag' &&
                $ref['_tag'] == 'input' && $ref['name'] == 'rev2[]'){
                if($meta['approval'][$ref['value']] ||
                        ($ref['value'] == 'current' && $meta['approval'][$latest_rev])) {
                  $event->data->_content[$member]['class'] = 'li approved_revision';
                }else{
                  $event->data->_content[$member]['class'] = 'li unapproved_revision';
                }
                $member = null;
            }
        }


        return true;
    }

    function handle_recent(&$event, $param) {
        #$meta = p_get_metadata($ID);
        #$latest_rev = $meta['last_change']['date'];

        $member = null;
        foreach($event->data->_content as $key => $ref) {
            if($ref['_elem'] == 'opentag' && $ref['_tag'] == 'div' && $ref['class'] == 'li') {
                $member = $key;
            }

            if($member && $ref['_elem'] == 'opentag' &&
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
                if($usename) {
                  if($this->hlp->in_namespace($this->getConf('apr_namespaces'), $usename)) {
                      $meta = p_get_metadata($usename);

                      if($meta['approval'][$meta['last_change']['date']]) {
                        $event->data->_content[$member]['class'] = 'li approved_revision';
                      }else{
                        $event->data->_content[$member]['class'] = 'li unapproved_revision';
                      }
                  }
                }
                $member = null;
            }
        }
        return true;
    }

    function difflink($id, $rev1, $rev2) {
        if($rev1 == $rev2) { return ''; }
        return '<a href="' . wl($id, 'rev2[]=' . $rev1 . '&rev2[]=' . $rev2 . '&do[diff]=1') .
          '" class="approved_diff_link">' .
          '<img src="'.DOKU_BASE.'lib/images/diff.png" class="approved_diff_link" alt="Diff" />' .
          '</a>';
    }

    function handle_start(&$event, $param) {
        # show only
        global $ACT;
        if($ACT != 'show') { return; }

        # only apply to latest rev
        global $REV;
        if($REV != '') { return; }

        # apply to readers only
        global $INFO;
        if($INFO['perm'] != AUTH_READ) { return; }

        # Check for override token
        global $_GET;
        if($_GET['force_rev']) { return; }

        # Only apply to appropriate namespaces
        global $ID;
        if(!$this->hlp->in_namespace($this->getConf('apr_namespaces'), $ID)) { return; }

        # Find latest rev
        $meta = p_get_metadata($ID);
        if($meta['approval'][$meta['last_change']['date']]) { return; } //REV=0 *is* approved

        if(!$meta['approval']) { return; } //no approvals

        # Get list of approvals
        $all = array_keys($meta['approval']);
        if(count($all) == 0) { return; } //no approvals

        $REV = $all[count($all)-1];
    }
}



