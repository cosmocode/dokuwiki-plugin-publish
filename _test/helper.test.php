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
     * Testdata for @see helper_plugin_publish_test::test_in_namespace
     *
     * @return array
     */
    public function in_namespace_testdata() {
        return array(
            array("de:sidebar en:sidebar", 'de:sidebar', true, ''),
            array("de:sidebar en:sidebar", 'en:sidebar', true, ''),
            array("de:sidebar en:sidebar", 'en:sidebar:', true, ''),
            array("de:sidebar en:sidebar", 'en:sidebar:start', true, ''),
            array("de:sidebar en:sidebar", 'en:sidebar:namespace:start', true, ''),
            array("de:sidebar en:sidebar", 'en:', false, ''),
            array("de:sidebar en:sidebar", '', false, ''),
            array("", 'foo:bar', true, ''),
        );
    }

    /**
     * @dataProvider in_namespace_testdata
     *
     * @param $namespace_list
     * @param $id
     * @param $result
     * @param $msg
     */
    public function test_in_namespace($namespace_list, $id, $result, $msg) {

        /** @var helper_plugin_publish $helper */
        $helper = plugin_load('helper', 'publish');
        $this->assertSame($helper->in_namespace($namespace_list,$id),$result,$msg);
    }

    /**
     * Testdata for @see helper_plugin_publish_test::test_in_sub_namespace
     *
     * @return array
     */
    public function in_sub_namespace_testdata() {
        return array(
            array("de:sidebar en:sidebar", 'de:sidebar', true, ''),
            array("de:sidebar en:sidebar", 'en:sidebar', true, ''),
            array("de:sidebar en:sidebar", 'en:sidebar:', true, ''),
            array("de:sidebar en:sidebar", 'en:sidebar:start', true, ''),
            array("de:sidebar en:sidebar", 'en:sidebar:namespace:start', true, ''),
            array("de:sidebar en:sidebar", 'en:', true, ''),
            array("de:sidebar en:sidebar", '', false, ''),
            array("", 'foo:bar', true, ''),
        );
    }

    /**
     * @dataProvider in_sub_namespace_testdata
     *
     * @param $namespace_list
     * @param $id
     * @param $result
     * @param $msg
     */
    public function test_in_sub_namespace($namespace_list, $id, $result, $msg) {

        /** @var helper_plugin_publish $helper */
        $helper = plugin_load('helper', 'publish');
        $this->assertSame($helper->is_dir_valid($namespace_list,$id),$result,$msg);
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
