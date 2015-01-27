<?php

/**
 * General tests for the publish plugin
 *
 * @group plugin_publish
 * @group plugins
 */
class approvel_test extends DokuWikiTest {

    public function setUp(){
        parent::setUp();

        global $USERINFO;
        $USERINFO = array(
           'pass' => '179ad45c6ce2cb97cf1029e212046e81',
           'name' => 'Arthur Dent',
           'mail' => 'arthur@example.com',
           'grps' => array ('admin','user'),
        );
        $_SERVER['REMOTE_USER'] = 'testuser';
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';

        global $INFO;
        $INFO = pageinfo();
    }

    protected $pluginsEnabled = array('publish');

    public function test_unaprroved_banner_exists() {
        saveWikiText('foo', 'bar', 'foobar');
        $request = new TestRequest();
        $response = $request->get(array('id' => 'foo'), '/doku.php?id=foo');
        $this->assertTrue(
            strpos($response->getContent(), '<div class="approval approved_no">') !== false,
            'The "not approved banner" is missing on a page which has not yet been aprroved with standard config.'
        );

    }

    public function test_aprroval_succesful() {
        global $USERINFO;
        global $INFO;
        saveWikiText('foo', 'bar', 'foobar');
        $request = new TestRequest();
        $_SERVER['REMOTE_USER'] = 'testuser';
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $response = $request->post(array('id' => 'foo'), '/doku.php?id=foo&publish_approve=1');
        print_r($INFO);
        print_r($response->getContent());
        print_r($USERINFO);
        print_r($_SERVER['REMOTE_USER']);

        $this->assertTrue(
            strpos($response->getContent(), '<div class="approval approved_yes">') !== false,
            'Approving a page failed with standard options.'
        );

    }

    public function test_no_aprroved_banner() {
        $conf['plugin']['publish']['hide approved banner'] = '1';
        saveWikiText('foo', 'bar', 'foobar');
        $request = new TestRequest();
        $response = $request->get(array('id' => 'foo'), '/doku.php?id=foo&publish_approve=1');
        $this->assertTrue(
            strpos($response->getContent(), '<div class="approval') === false,
            'The approved banner is still showing even so it is supposed not to show.'
        );

    }
}
