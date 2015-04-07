<?php
/**
 * General tests for the publish plugin
 *
 * @group plugin_publish
 * @group plugin_publish_integration
 * @group plugins
 * @group integrationtests
 * @author Michael Große <grosse@cosmocode.de>
 */
class publish_mail_test extends DokuWikiTest {

    protected $pluginsEnabled = array('publish');

    public function setUp() {
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
        $conf['superuser'] = '@admin';
    }

    /**
     * Blackbox integration test of action_plugin_publish_mail::getLastApproved
     *
     * @coversNothing
     */
    public function test_getLastApproved () {
        global $ID;
        $ID = 'foo';
        saveWikiText('foo', 'bar old', 'foobar');
        saveWikiText('foo', 'bar approved', 'foobar');
        $data = pageinfo();
        $expected_revision = $data['currentrev'];

        //Make sure we have the rights to actully approve a revision
        $this->assertSame(255,auth_quickaclcheck('foo'));

        $request = new TestRequest();
        $request->get(array(), '/doku.php?id=foo&publish_approve');

        saveWikiText('foo', 'bar new', 'foobar');

        /** @var helper_plugin_publish $helper */
        $helper = plugin_load('helper','publish');
        $actual_lastapproved_helper = $helper->getLatestApprovedRevision($ID);

        $this->assertSame($expected_revision, $actual_lastapproved_helper);
    }


}
