<?php

/**
 * Direct integration with Viber
 * */
class erLhcoreClassExtensionViber
{
    private static $persistentSession;

    public function __construct()
    {
        $dispatcher = erLhcoreClassChatEventDispatcher::getInstance();
        $dispatcher->listen('chat.webhook_incoming', 'erLhcoreClassExtensionViber::incommingWebhook');
    }

    /*
     * erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.webhook_incoming', array(
        'webhook' => & $incomingWebhook,
        'data' => & $data
    ));*/
    public static function incommingWebhook($params)
    {
        if (isset($params['data']['event']) && in_array($params['data']['event'], ['delivered','seen','failed'])) {

            $incomingChat = erLhcoreClassModelChatIncoming::findOne(array('filter' => array('incoming_id' => $params['webhook']->id, 'chat_external_id' => $params['data']['user_id'])));

            // Chat was found, now we need to find exact message
            if ($incomingChat instanceof erLhcoreClassModelChatIncoming && is_object($incomingChat->chat)) {
                $statusMap = [
                    'pending' => erLhcoreClassModelmsg::STATUS_PENDING,
                    'sent' => erLhcoreClassModelmsg::STATUS_SENT,
                    'delivered' => erLhcoreClassModelmsg::STATUS_DELIVERED,
                    'seen' =>  erLhcoreClassModelmsg::STATUS_READ,
                    'failed' =>  erLhcoreClassModelmsg::STATUS_REJECTED
                ];
                $msg = erLhcoreClassModelmsg::findOne(['filter' => ['chat_id' => $incomingChat->chat->id], 'customfilter' => ['`meta_msg` != \'\' AND JSON_EXTRACT(meta_msg,\'$.iwh_msg_id\') = ' . ezcDbInstance::get()->quote($params['data']['message_token'])]]);

                if (is_object($msg) && $msg->del_st != erLhcoreClassModelmsg::STATUS_READ) {

                    $msg->del_st = max($statusMap[$params['data']['event']],$msg->del_st);
                    $msg->updateThis(['update' => ['del_st']]);

                    // Refresh message delivery status for op
                    $chat = $incomingChat->chat;
                    $chat->operation_admin .= "lhinst.updateMessageRowAdmin({$msg->chat_id},{$msg->id});";
                    if ($msg->del_st == erLhcoreClassModelmsg::STATUS_READ) {
                        $chat->has_unread_op_messages = 0;
                    }
                    $chat->updateThis(['update' => ['operation_admin','has_unread_op_messages']]);

                    // NodeJS to update message delivery status
                    erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.message_updated', array('msg' => & $msg, 'chat' => & $chat));
                }
            }

            exit;
        }
    }

    public function run()
    {

    }
}