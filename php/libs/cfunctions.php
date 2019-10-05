<?php
function is_empty($var) {
    if(is_array($var)) {
        return (count($var) == 0);
    };
    return (((strlen($var) == 0) || is_null($var)));
}

function replace_spaces($str, $replacement = "") {
    return preg_replace('/\s+/', $replacement, $str);
}

function remove_spaces($str) {
    return replace_spaces($str);
}

function die_redirect($error = "UNKNOWN_ERROR") {
    die('error: '.$error);
}

function compare($str1, $str2, $case_sensitive = false) {
    $str1 = remove_spaces($str1);
    $str2 = remove_spaces($str2);
    return ((($case_sensitive) ? strcmp($str1, $str2) : strcasecmp($str1, $str2)) == 0);
}
?>
