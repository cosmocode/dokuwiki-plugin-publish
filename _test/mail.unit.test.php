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
        $mail = new action_plugin_publish_mail;
        $actual_difflink = $mail->difflink('wiki:syntax','1','2');
        print_r($actual_difflink);
        $this->markTestIncomplete('Test must yet be implemented.');
    }

    /**
     * @covers action_plugin_publish_mail::difflink
     */
    function test_apprejlink () {
        global $ID;
        $ID = 'wiki:syntax';
        $mail = new action_plugin_publish_mail;
        $actual_apprejlink = $mail->apprejlink('wiki:syntax','1');
        print_r($actual_apprejlink);
        $expected_apprejlink = 'http://wiki.example.com/doku.php?id=wiki:syntax&rev=1&do=edit&suggestfrom=';
        $this->assertSame($expected_apprejlink,$actual_apprejlink);
        $this->markTestIncomplete('Test must yet be implemented.');
    }
}
