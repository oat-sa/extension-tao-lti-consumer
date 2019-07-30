<?php
use oat\tao\helpers\Template;
Template::inc('form_context.tpl', 'tao');
?>
<?= tao_helpers_Scriptloader::render() ?>
<header class="section-header flex-container-full">
    <h2><?=get_data('formTitle')?></h2>
</header>

<div class="main-container">

    <div class="multi-form-container">

        <nav class="tab-selector"></nav>

        <div class="compiled-delivery-form-content" data-tab-content="tao-local" data-tab-label="TAO Local">
            <?php if (!empty(get_data('compiled-form-message'))): ?>
                <div class="feedback-warning">
                    <span class="icon-warning"></span><?= get_data('compiled-form-message') ?>
                </div>
                <div>
                    <a class="btn-info" href="<?=_url('index', 'Main', 'tao', array('structure' => 'tests', 'ext' => 'taoTests'))?>"><?=__('Create a test')?></a>
                </div>
            <?php endif; ?>
            <?= get_data('compiled-delivery-form') ?>
        </div>

        <div class="lti-delivery-form-content hidden" data-tab-content="lti-based" data-tab-label="LTI-based">
            <?php if (!empty(get_data('lti-form-message'))): ?>
                <div class="feedback-warning">
                    <span class="icon-warning"></span><?= get_data('lti-form-message') ?>
                </div>
                <div>
                    <a class="btn-info" href="<?=_url('index', 'Main', 'tao', array('structure' => 'settings', 'ext' => 'taoLti', 'section' => 'settings_oauth_mng_provider'))?>"><?=__('Create a LTI provider')?></a>
                </div>
            <?php endif; ?>
            <?= get_data('lti-delivery-form') ?>
        </div>

    </div>

</div>

<div class="data-container-wrapper flex-container-remaining"></div>

<?php Template::inc('footer.tpl', 'tao'); ?>
