<?php

$tpl = erLhcoreClassTemplate::getInstance('lhviber/settings.tpl.php');

$mbOptions = erLhcoreClassModelChatConfig::fetch('viber_options');
$data = (array)$mbOptions->data;

if (isset($_POST['StoreOptions'])) {

    if (!isset($_POST['csfr_token']) || !$currentUser->validateCSFRToken($_POST['csfr_token'])) {
        erLhcoreClassModule::redirect('viber/settings');
        exit;
    }

    $definition = array(
        'bot_token' => new ezcInputFormDefinitionElement(
            ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
        )
    );

    $form = new ezcInputForm( INPUT_POST, $definition );
    $Errors = array();

    if ( $form->hasValidData( 'bot_token' )) {
        $data['bot_token'] = $form->bot_token;
    } else {
        $data['bot_token'] = '';
    }

    $mbOptions->explain = '';
    $mbOptions->type = 0;
    $mbOptions->hidden = 1;
    $mbOptions->identifier = 'viber_options';
    $mbOptions->value = serialize($data);
    $mbOptions->saveThis();

    $incomingWebhook = \erLhcoreClassModelChatIncomingWebhook::findOne(['filter' => ['name' => 'ViberIntegration']]);

    if (is_object($incomingWebhook)) {
        $conditionsArray = $incomingWebhook->conditions_array;
        if (isset($conditionsArray['attr']) && is_array($conditionsArray['attr'])) {
            foreach ($conditionsArray['attr'] as $attrIndex => $attrValue) {
                if ($attrValue['key'] == 'bot_token') {
                    $attrValue['value'] = $data['bot_token'];
                    $conditionsArray['attr'][$attrIndex] = $attrValue;
                }
            }
        }
        $incomingWebhook->conditions_array = $conditionsArray;
        $incomingWebhook->configuration = json_encode($conditionsArray);
        $incomingWebhook->updateThis(['update' => ['configuration']]);
    }

    $tpl->set('updated','done');
}

if (isset($_POST['CreateUpdateRestAPI'])) {
    \LiveHelperChatExtension\viber\providers\ViberLiveHelperChatActivator::installOrUpdate();
    $tpl->set('updated','done');
}

if (isset($_POST['RemoveRestAPI'])) {
    \LiveHelperChatExtension\viber\providers\ViberLiveHelperChatActivator::remove();
    $tpl->set('updated','done');
}

$tpl->set('mb_options',$data);

$Result['content'] = $tpl->fetch();

$Result['path'] = array(
    array(
        'url' => erLhcoreClassDesign::baseurl('viber/settings'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('messagebird/module','Viber settings')
    )
);

?>