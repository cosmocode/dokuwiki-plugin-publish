<?php


if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

function in_namespace($valid, $check) {
    // PHP apparantly does not have closures -
    // so we will parse $valid ourselves. Wasteful.
    $valid = preg_split('/\s+/', $valid);
    //if(count($valid) == 0) { return true; }//whole wiki matches
    if((count($valid)==1) and ($valid[0]=="")) { return true; }//whole wiki matches
    $check = trim($check, ':');
    $check = explode(':', $check);

    // Check against all possible namespaces
    foreach($valid as $v) {
        $v = explode(':', $v);
        $n = 0;
        $c = count($v);
        $matching = 1;

        // Check each element, untill all elements of $v satisfied
        while($n < $c) {
            if($v[$n] != $check[$n]) {
                // not a match
                $matching = 0;
                break;
            }
            $n += 1;
        }
        if($matching == 1) { return true; } // a match
    }
    return false;
}

function in_sub_namespace($valid, $check) {
    // is check a dir which contains any valid?
    // PHP apparantly does not have closures -
    // so we will parse $valid ourselves. Wasteful.
    $valid = preg_split('/\s+/', $valid);
    //if(count($valid) == 0) { return true; }//whole wiki matches
    if((count($valid)==1) and ($valid[0]=="")) { return true; }//whole wiki matches
    $check = trim($check, ':');
    $check = explode(':', $check);

    // Check against all possible namespaces
    foreach($valid as $v) {
        $v = explode(':', $v);
        $n = 0;
        $c = count($check); //this is what is different from above!
        $matching = 1;

        // Check each element, untill all elements of $v satisfied
        while($n < $c) {
            if($v[$n] != $check[$n]) {
                // not a match
                $matching = 0;
                break;
            }
            $n += 1;
        }
        if($matching == 1) { return true; } // a match
    }
    return false;
}

