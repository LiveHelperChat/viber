<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('messagebird/module','Integration settings'); ?></h1>

<form action="" method="post" ng-non-bindable>

    <?php include(erLhcoreClassDesign::designtpl('lhkernel/csfr_token.tpl.php'));?>

    <?php if (isset($updated) && $updated == 'done') : $msg = erTranslationClassLhTranslation::getInstance()->getTranslation('messagebird/module','Settings updated'); ?>
        <?php include(erLhcoreClassDesign::designtpl('lhkernel/alert_success.tpl.php'));?>
    <?php endif; ?>

    <div class="form-group">
        <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('messagebird/module','Bot Token'); ?></label>
        <input type="text" name="bot_token" placeholder="XXXXXXXXXXXXX-XXXXXXXXXXXXX-XXXXXXXXXXXXX" class="form-control form-control-sm" value="<?php isset($mb_options['bot_token']) ? print htmlspecialchars($mb_options['bot_token']) : ''?>" />
    </div>

    <div class="btn-group" role="group" aria-label="Basic example">
        <button name="StoreOptions" class="btn btn-sm btn-secondary" type="submit"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('messagebird/module','Save'); ?></button>

        <?php if (isset($mb_options['bot_token']) && !empty($mb_options['bot_token'])) : ?>
        <button name="CreateUpdateRestAPI" class="btn btn-sm btn-info"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('messagebird/module','Create/Update Rest API call'); ?></button>
        <?php endif; ?>

        <?php if (\erLhcoreClassModelGenericBotRestAPI::getCount(['filter' => ['name' => 'ViberIntegration']]) == 1) : ?>
        <button name="RemoveRestAPI" class="btn btn-sm btn-danger"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('messagebird/module','Remove Rest API call'); ?></button>
        <?php endif; ?>
    </div>

</form>