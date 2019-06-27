<form action="<?= get_data('launchUrl') ?>" method="post" id="launch-test-form">
<?php 
foreach (get_data('launchParams') as $key => $value) {
    echo $key.' => '.$value.'</br>';
    echo '<input type="hidden" name="'.$key.'" value="'.$value.'"/>'.PHP_EOL;
}
?>
    <input type="submit"/>
</form>