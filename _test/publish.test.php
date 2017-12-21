<?php

/**
 * General tests for the publish plugin
 *
 * @group plugin_publish_integration
 * @group plugin_publish
 * @group plugins
 * @group integrationtests
 * @author Michael Große <grosse@cosmocode.de>
 */
class approvel_test extends DokuWikiTest {

    protected $pluginsEnabled = array('publish');

    public function setUp(){
        parent::setUp();

        global $USERINFO;
        $USERINFO = array(
           'pass' => '179ad45c6ce2cb97cf1029e212046e81',
           'name' => 'Arthur Dent',
           'mail' => 'arthur@example.com',
           'grps' => array ('admin','user'),
        );

        global $default_server_vars;
        $default_server_vars['REMOTE_USER'] = 'testuser'; //Hack until Issue splitbrain/dokuwiki#1099 is fixed

        $_SERVER['REMOTE_USER'] = 'testuser';

        global $conf;
        global $AUTH_ACL;
        $conf['useacl']    = 1;
        $conf['superuser'] = '@admin';
        $AUTH_ACL = array(
            '*                     @ALL        4',
            '*                     @admin     16',);
    }

    /**
     * @coversNothing
     */
    public function test_unaprroved_banner_exists() {
        saveWikiText('foo', 'bar', 'foobar');
        $request = new TestRequest();
        $response = $request->get(array('id' => 'foo'), '/doku.php?id=foo');
        $this->assertTrue(
            strpos($response->getContent(), '<div class="approval approved_no">') !== false,
            'The "not approved banner" is missing on a page which has not yet been aprroved with standard config.'
        );

    }

    /**
     * @coversNothing
     */
    public function test_aprroval_succesful() {
        saveWikiText('foo', 'bar', 'foobar');
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=foo&publish_approve=1');
        $this->assertTrue(
            strpos($response->getContent(), '<div class="approval approved_yes">') !== false,
            'Approving a page failed with standard options.'
        );

    }

    /**
     * @coversNothing
     */
    public function test_no_aprroved_banner() {
        global $conf;
        $conf['plugin']['publish']['hide_approved_banner'] = 1;
        saveWikiText('foo', 'bar', 'foobar');

        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=foo&publish_approve=1');

        $this->assertTrue(
            strpos($response->getContent(), '<div class="approval') === false,
            'The approved banner is still showing even so it is supposed not to show.'
        );
    }

    private function _prepare_revisions_and_test_common(){
        // nothing, @admin should see 404
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=nonexist');
        $this->assertTrue(
            strpos($response->getContent(), 'mode_show') !== false,
            'Visiting a non-exist page returns denied message.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'notFound') !== false,
            'Visiting a non-exist page did not return notFound message.'
        );

        // init one approved and one draft
        saveWikiText('foo', 'This should get APPROVED', 'approved');
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=foo&publish_approve=1');
        $this->assertTrue(
            strpos($response->getContent(), '<div class="approval approved_yes">') !== false,
            'Approving a page failed with standard options.'
        );

        saveWikiText('foo', 'This should be a DRAFT', 'draft');
        $draft_rev = @filemtime(wikiFN('foo'));

        // draft-only page
        saveWikiText('draft_only', 'This should be a DRAFT', 'draft');

        // a user with AUTH_EDIT or better will see the latest revision of a page
        // @admin should see the draft
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=foo');
        $this->assertTrue(
            strpos($response->getContent(), 'mode_show') !== false,
            'Visiting a page did not return in show mode.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'This should be a DRAFT') !== false,
            'Visiting a page with draft did not return draft revision.'
        );

        // switch to @ALL - AUTH_READ
        global $USERINFO;
        $USERINFO = null;

        global $default_server_vars;
        $default_server_vars['REMOTE_USER'] = null; //Hack until Issue splitbrain/dokuwiki#1099 is fixed

        $_SERVER['REMOTE_USER'] = null;

        // @ALL should see approved revision
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=foo');
        $this->assertTrue(
            strpos($response->getContent(), 'mode_show') !== false,
            'Visiting a page did not return in show mode.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'This should get APPROVED') !== false,
            'Visiting a page with draft with AUTH_READ did not return approved revision.'
        );

        return $draft_rev;
    }

    /**
     * @coversNothing
     */
    public function test_show_expected_revision(){
        global $conf;

        $draft_rev = $this->_prepare_revisions_and_test_common();

        // someone with only AUTH_READ will see the latest approved revision by default (unless there isn't one)
        // page with no approved revision: show draft
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=draft_only');
        $this->assertTrue(
            strpos($response->getContent(), 'mode_show') !== false,
            'Visiting a draft-only page did not return in show mode.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'This should be a DRAFT') !== false,
            'Visiting a draft-only page with AUTH_READ did not return draft revision.'
        );

        // all users with AUTH_READ or better can view any revision of a page if they specifically request it – whether or not it is approved
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=foo&rev='.$draft_rev);
        $this->assertTrue(
            strpos($response->getContent(), 'mode_show') !== false,
            'Visiting a draft revision did not return in show mode.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'This should be a DRAFT') === false,
            'Visiting a draft revision with AUTH_READ did not return draft content.'
        );

        // nothing, @ALL should see 404
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=nonexist');
        $this->assertTrue(
            strpos($response->getContent(), 'mode_show') !== false,
            'Visiting a non-exist page returns denied message.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'notFound') !== false,
            'Visiting a non-exist page did not return notFound message.'
        );
    }

    /**
     * @coversNothing
     */
    public function test_show_expected_revision_hide_drafts(){
        global $conf;
        $conf['plugin']['publish']['hide drafts'] = 1;

        $draft_rev = $this->_prepare_revisions_and_test_common();

        // page with no approved revision: hide draft
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=draft_only');
        $this->assertTrue(
            strpos($response->getContent(), 'mode_denied') !== false,
            'Visiting a draft-only page with hide_drafts on did not return in denied mode.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'This should be a DRAFT') === false,
            'Visiting a draft-only page with hide_drafts on with AUTH_READ returns draft content.'
        );

        // specifically request revision: without approval permission, deny it
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=foo&rev='.$draft_rev);
        $this->assertTrue(
            strpos($response->getContent(), 'mode_denied') !== false,
            'Visiting a draft-only page did not return in denied mode.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'This should be a DRAFT') === false,
            'Visiting a draft revision with hide_drafts on with AUTH_READ returns draft content.'
        );

        // nothing, @ALL should see 404
        $request = new TestRequest();
        $response = $request->get(array(), '/doku.php?id=nonexist');
        $this->assertTrue(
            strpos($response->getContent(), 'mode_show') !== false,
            'Visiting a non-exist page returns denied message.'
        );
        $this->assertTrue(
            strpos($response->getContent(), 'notFound') !== false,
            'Visiting a non-exist page did not return notFound message.'
        );
    }
}
