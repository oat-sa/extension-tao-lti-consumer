<?php
use oat\tao\helpers\Template;
Template::inc('form_context.tpl', 'tao');
?>
<?= tao_helpers_Scriptloader::render() ?>
<header class="section-header flex-container-full">
    <h2><?=get_data('formTitle')?></h2>
</header>


<div class="main-container">

    <div class="flex-container-main-form">
        <div class="outer-container">
        <div class="form-switch"></div>

        <div class="compiled-delivery-form-content">
            <?=get_data('compiled-delivery-form')?>
        </div>

        <div class="lti-delivery-form-content">
            <?=get_data('lti-delivery-form')?>
        </div></div>
    </div>

</div>
<div class="data-container-wrapper flex-container-remaining"></div>


<?php Template::inc('footer.tpl', 'tao'); ?>