<?php
/**
 * tests or the class helper_plugin_publish of the publish plugin
 *
 * @group plugin_publish
 * @group plugins
 */
class helper_plugin_publish_test extends DokuWikiTest {

    public function setUp() {
        parent::setUp();
    }

    protected $pluginsEnabled = array('publish',);

    /**
     *
     */
    public function test_in_namespace() {

        /** @var helper_plugin_publish $helper */
        $helper = plugin_load('helper', 'publish');
        $this->assertTrue($helper->in_namespace("de:sidebar en:sidebar", 'de:sidebar'));
        $this->assertTrue($helper->in_namespace("de:sidebar en:sidebar", 'en:sidebar'));
    }

    public function test_isActive() {
        global $conf;

        $conf['plugin']['publish']['no_apr_namespaces'] = 'de:sidebar en:sidebar';

        /** @var helper_plugin_publish $helper */
        $helper = plugin_load('helper', 'publish');

        $this->assertFalse($helper->isActive('de:sidebar'), 'de:sidebar is still listed as active');
        $this->assertFalse($helper->isActive('en:sidebar'), 'en:sidebar is still listed as active');
    }

}