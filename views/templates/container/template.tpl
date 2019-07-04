<h1>Launching test...</h1>

<form action="<?= get_data('launchUrl') ?>" method="post" id="launch-test-form" style="display:none;"">
<?php 
foreach (get_data('launchParams') as $key => $value) {
    echo $key.' => '.$value.'</br>';
    echo '<input type="hidden" name="'.$key.'" value="'.$value.'"/>'.PHP_EOL;
}
?>
    <input type="submit"/>
</form>
<script>
    form = document.getElementById('launch-test-form');
    form.submit();
</script>
