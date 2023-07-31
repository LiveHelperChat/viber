<?php if (erLhcoreClassUser::instance()->hasAccessTo('lhviber','configure')) : ?>
    <li class="nav-item"><a class="nav-link" href="<?php echo erLhcoreClassDesign::baseurl('viber/settings')?>"><i class="material-icons">integration_instructions</i><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('messagebird/module','Viber');?></a></li>
<?php endif; ?>
