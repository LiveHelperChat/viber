<?php

namespace LiveHelperChatExtension\viber\providers;
#[\AllowDynamicProperties]
class ViberLiveHelperChatActivator {

    // Remove SMS
    public static function remove()
    {
        if ($incomingWebhook = \erLhcoreClassModelChatIncomingWebhook::findOne(['filter' => ['name' => 'ViberIntegration']])) {
            $incomingWebhook->removeThis();
        }

        if ($restAPI = \erLhcoreClassModelGenericBotRestAPI::findOne(['filter' => ['name' => 'ViberIntegration']])) {
            $restAPI->removeThis();
        }

        if ($botPrevious = \erLhcoreClassModelGenericBotBot::findOne(['filter' => ['name' => 'ViberIntegration']])) {
            $botPrevious->removeThis();

            if ($event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.desktop_client_admin_msg', 'bot_id' => $botPrevious->id]]])) {
                $event->removeThis();
            }

            if ($event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.workflow.canned_message_before_save', 'bot_id' => $botPrevious->id]]])) {
                $event->removeThis();
            }

            if ($event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.web_add_msg_admin', 'bot_id' => $botPrevious->id]]])) {
                $event->removeThis();
            }

            if ($event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.before_auto_responder_msg_saved', 'bot_id' => $botPrevious->id]]])) {
                $event->removeThis();
            }

        }
    }

    public static function setWebhookURL($botToken, $webhookURL)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://chatapi.viber.com/pa/set_webhook');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $headers = array('Accept: application/json', 'User-Agent: LHC RestAPI');

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{
   "url":"' . $webhookURL . '",
   "event_types":[
      "delivered",
      "seen",
      "failed",
      "subscribed",
      "unsubscribed",
      "conversation_started"
   ],
   "send_name": true,
   "send_photo": true
}');

        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Expect:';
        $headers[] = 'X-Viber-Auth-Token: ' . $botToken;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $startTime = date('H:i:s');
        $additionalError = ' ';

        $content = curl_exec($ch);

        if (curl_errno($ch)) {
            $additionalError = ' [ERR: ' . curl_error($ch) . '] ';
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $endTime = date('H:i:s');

        self::$lastCallDebug['request_url_response'][] = '[T' . 15 . '] ['.$httpcode.']'.$additionalError.'['.$startTime . ' ... ' . $endTime.'] - :' . $content;

        if ($httpcode == 204) {
            return array();
        }

        if ($httpcode == 404) {
            throw new Exception('Resource could not be found!');
        }

        if ($httpcode == 401) {
            throw new Exception('No permission to access resource!');
        }

        if ($httpcode == 200 && $content !== false) {
             return $content;
        } else {
             throw new Exception('Invalid response was returned');
        }

        if ($httpcode == 500) {
            throw new Exception('Invalid response was returned');
        }
    }

    public static $lastCallDebug = array();

    // Install SMS
    public static function installOrUpdate()
    {
        $mbOptions = \erLhcoreClassModelChatConfig::fetch('viber_options');
        $data = (array)$mbOptions->data;

        $incomingWebhook = \erLhcoreClassModelChatIncomingWebhook::findOne(['filter' => ['name' => 'ViberIntegration']]);

        $incomingWebhookContent = str_replace('{bot_token}', $data['bot_token'], file_get_contents('extension/viber/doc/configs/iwh-viber.json'));
        $content = json_decode($incomingWebhookContent,true);

        if (!$incomingWebhook) {
            $incomingWebhook = new \erLhcoreClassModelChatIncomingWebhook();
            $incomingWebhook->setState($content);
            $incomingWebhook->dep_id = 1;
            $incomingWebhook->name = 'ViberIntegration';
            $incomingWebhook->identifier = \erLhcoreClassModelForgotPassword::randomPassword(20);
        } else {
            $dep_id = $incomingWebhook->dep_id;
            $identifier = $incomingWebhook->identifier;
            $incomingWebhook->setState($content);
            $incomingWebhook->dep_id = $dep_id;
            $incomingWebhook->identifier = $identifier;
            $incomingWebhook->name = 'ViberIntegration';
        }
        $incomingWebhook->saveThis();

        // Set bot WebHook
        $responseSetWebhookRaw = self::setWebhookURL($data['bot_token'], \erLhcoreClassSystem::getHost() . \erLhcoreClassDesign::baseurl('webhooks/incoming') . '/' . $incomingWebhook->identifier);
        $responseSetWebhook = json_decode($responseSetWebhookRaw,true);

        if (!isset($responseSetWebhook['status_message']) || $responseSetWebhook['status_message'] != 'ok') {
            throw new \Exception('Setting webhook failed with an error.'.$responseSetWebhookRaw);
        }

        // RestAPI
        $restAPI = \erLhcoreClassModelGenericBotRestAPI::findOne(['filter' => ['name' => 'ViberIntegration']]);
        $content = json_decode(file_get_contents('extension/viber/doc/configs/restapi-viber.json'),true);

        if (!$restAPI) {
            $restAPI = new \erLhcoreClassModelGenericBotRestAPI();
        }

        $restAPI->setState($content);
        $restAPI->name = 'ViberIntegration';
        $restAPI->saveThis();

        if ($botPrevious = \erLhcoreClassModelGenericBotBot::findOne(['filter' => ['name' => 'ViberIntegration']])) {
            $botPrevious->removeThis();
        }

        $botData = \erLhcoreClassGenericBotValidator::importBot(json_decode(file_get_contents('extension/viber/doc/configs/bot-viber.json'),true));
        $botData['bot']->name = 'ViberIntegration';
        $botData['bot']->updateThis(['update' => ['name']]);

        $trigger = $botData['triggers'][0];
        $actions = $trigger->actions_front;
        $actions[0]['content']['rest_api'] = $restAPI->id;
        $trigger->actions_front = $actions;
        $trigger->actions = json_encode($actions);
        $trigger->updateThis(['update' => ['actions']]);

        if ($botPrevious && $event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.desktop_client_admin_msg', 'bot_id' => $botPrevious->id]]])) {
            $event->removeThis();
        }
        $event = new \erLhcoreClassModelChatWebhook();
        $event->setState(json_decode(file_get_contents('extension/viber/doc/configs/chat.desktop_client_admin_msg.json'),true));
        $event->bot_id = $botData['bot']->id;
        $event->trigger_id = $trigger->id;
        $event->saveThis();

        if ($botPrevious && $event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.workflow.canned_message_before_save', 'bot_id' => $botPrevious->id]]])) {
            $event->removeThis();
        }
        $event = new \erLhcoreClassModelChatWebhook();
        $event->setState(json_decode(file_get_contents('extension/viber/doc/configs/chat.workflow.canned_message_before_save.json'),true));
        $event->bot_id = $botData['bot']->id;
        $event->trigger_id = $trigger->id;
        $event->saveThis();

        if ($botPrevious && $event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.web_add_msg_admin', 'bot_id' => $botPrevious->id]]])) {
            $event->removeThis();
        }
        $event = new \erLhcoreClassModelChatWebhook();
        $event->setState(json_decode(file_get_contents('extension/viber/doc/configs/chat.web_add_msg_admin.json'),true));
        $event->bot_id = $botData['bot']->id;
        $event->trigger_id = $trigger->id;
        $event->saveThis();

        if ($botPrevious && $event = \erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => ['chat.before_auto_responder_msg_saved', 'bot_id' => $botPrevious->id]]])) {
            $event->removeThis();
        }
        $event = new \erLhcoreClassModelChatWebhook();
        $event->setState(json_decode(file_get_contents('extension/viber/doc/configs/chat.before_auto_responder_msg_saved.json'),true));
        $event->bot_id = $botData['bot']->id;
        $event->trigger_id = $trigger->id;
        $event->saveThis();
    }
}

?>