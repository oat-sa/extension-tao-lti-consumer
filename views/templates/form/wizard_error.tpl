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
        <div class="feedback-warning">
            <span class="icon-warning"></span><?= get_data('message') ?>
        </div>
        <div>
            <a class="btn-info" href="<?=_url('index', 'Main', 'tao', array('structure' => 'tests', 'ext' => 'taoTests'))?>"><?=__('Create a test')?></a>
            <a class="btn-info" href="<?=_url('index', 'Main', 'tao', array('structure' => 'settings', 'ext' => 'taoLti', 'section' => 'settings_oauth_mng_provider'))?>"><?=__('Create a LTI provider')?></a>
        </div>
    </div>
</div>