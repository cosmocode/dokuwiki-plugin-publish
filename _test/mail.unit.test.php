<?php
/**
 * Unittests for the mail functionality of the publish plugin
 *
 * @group plugin_publish
 * @group plugin_publish_unittests
 * @group plugins
 * @group unittests
 * @author Michael Große <grosse@cosmocode.de>
 */
class publish_mail_unit_test extends DokuWikiTest {

    protected $pluginsEnabled = array('publish');

    /**
     * @covers action_plugin_publish_mail::difflink
     */
    function test_difflink () {
        global $ID;
        $ID = 'wiki:syntax';

        /** @var helper_plugin_publish $helper*/
        $helper = plugin_load('helper','publish');
        $actual_difflink = $helper->getDifflink('wiki:syntax','1','2');
        $expected_difflink = 'http://wiki.example.com/./doku.php?id=wiki:syntax&do=diff&rev2[0]=1&rev2[1]=2&difftype=sidebyside';
        $this->assertSame($expected_difflink,$actual_difflink);
    }

    /**
     * @covers action_plugin_publish_mail::apprejlink
     */
    function test_apprejlink () {
        global $ID;
        $ID = 'wiki:syntax';
        $mail = new action_plugin_publish_mail;
        $actual_apprejlink = $mail->apprejlink('wiki:syntax','1');
        $expected_apprejlink = 'http://wiki.example.com/./doku.php?id=wiki:syntax&rev=1'; //this stray dot comes from an unclean test-setup
        $this->assertSame($expected_apprejlink, $actual_apprejlink);
    }
}
