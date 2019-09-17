<?php
namespace oat\taoLtiConsumer\model\result;

use oat\taoLtiConsumer\model\result\XmlFormatterService;

class MessagesService
{
    const SERVICE_ID = 'result_service';
    const FAILURE_MESSAGE = 'failure';
    const SUCCESS_MESSAGE = 'success';

    const STATUS_INVALID_SCORE = 400;
    const STATUS_DELIVERY_EXECUTION_NOT_FOUND = 404;
    const STATUS_METHOD_NOT_IMPLEMENTED = 501;
    const STATUS_INTERNAL_SERVER_ERROR = 500;
    const STATUS_SUCCESS = 201;

    public static $statuses = array(
        self::STATUS_INVALID_SCORE => 'Invalid score',
        self::STATUS_DELIVERY_EXECUTION_NOT_FOUND => 'DeliveryExecution not found',
        self::STATUS_METHOD_NOT_IMPLEMENTED => 'Method not implemented',
        self::STATUS_INTERNAL_SERVER_ERROR => 'Internal server error, please retry',
    );

    public static function buildMessageData($code, $result)
    {
        $message = self::FAILURE_MESSAGE;
        $description = self::$statuses[self::STATUS_DELIVERY_EXECUTION_NOT_FOUND];
        $sourcedId = isset($result['sourcedId']) ? $result['sourcedId'] : '';
        $messageIdentifier = isset($result['messageIdentifier']) ? $result['messageIdentifier'] : '';

        if ($code === self::STATUS_SUCCESS) {
            $sourcedId = $result['sourcedId'];
            $message = self::SUCCESS_MESSAGE;
            $description = str_replace(
                ['{{sourceId}}', '{{score}}'],
                [$sourcedId, $result['score']],
                XmlFormatterService::SCORE_DESCRIPTION_TEMPLATE
            );
        }

        return [
            XmlFormatterService::TEMPLATE_VAR_CODE_MAJOR => $message,
            XmlFormatterService::TEMPLATE_VAR_DESCRIPTION => $description,
            XmlFormatterService::TEMPLATE_VAR_MESSAGE_ID => $messageIdentifier,
            XmlFormatterService::TEMPLATE_VAR_MESSAGE_REF_IDENTIFIER => $sourcedId,
        ];
    }
}