
<?php 

/* 
 * this only gets rendered if there are no errors when connecting a user to linkedin
 *
 */

echo $this->Html->scriptBlock("

    if(!window.opener.closed){
        window.opener.alertUser();
        window.close();
    } else {
        alert('Unable to connect to Simply Sent browser window');
    }

");
?>