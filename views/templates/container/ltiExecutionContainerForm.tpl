<?php
use oat\tao\helpers\Template;
?>

<h1>Launching test...</h1>

<form action="<?= get_data('launchUrl') ?>" method="post" id="launch-test-form" style="display:none;"">
<?php 
foreach (get_data('launchParams') as $key => $value):
    echo '<input type="hidden" name="' . $key . '" value="' . $value . '"/>' . PHP_EOL;
endforeach;
?>
    <input type="submit"/>
</form>
<script src="<?= Template::js('ltiConsumerLauncher.js', 'taoLtiConsumer')?>"></script>
