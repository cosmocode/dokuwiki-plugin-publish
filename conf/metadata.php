<?php

$meta['apr_namespaces'] = array('string');
$meta['no_apr_namespaces'] = array('string');
$meta['number_of_approved'] = array('numeric', '_min' => 1);
$meta['hide drafts'] = array('onoff');
$meta['hidereaderbanner'] = array('onoff');
$meta['hide_approved_banner'] = array('onoff');
$meta['author groups'] = array('string');
$meta['internal note'] = array('string');
$meta['delete attic on first approve'] = array('onoff');

$meta['send_mail_on_approve'] = array('onoff');
$meta['apr_mail_receiver'] = array('string');
$meta['apr_approved_text'] = array('string');
