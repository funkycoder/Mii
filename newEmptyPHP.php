<?php


if (!isset($_POST['myacc'])) {
    echo 'Post myacc is not set';
} else {
    echo 'my acc is set';
}
$myAcc='';
        
$myAcc = $_POST['myacc'];
if (!isset($myAcc)) {
    echo 'var myacc is not set';
} else {
    echo 'my acc is set';
}
?>
