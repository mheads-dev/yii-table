<?php

declare(strict_types=1);

use Mheads\Yii\Table\Http\Request\TableRequestApplier;
use Mheads\Yii\Table\Http\Request\TableRequestApplierInterface;
use Mheads\Yii\Table\Http\Response\Payload\JsonTablePayloadResponder;
use Mheads\Yii\Table\Http\Response\Payload\TablePayloadResponderInterface;
use Mheads\Yii\Table\Http\Response\TableHttpResponder;
use Mheads\Yii\Table\Http\Response\TableHttpResponderInterface;

return [
    TableRequestApplierInterface::class => TableRequestApplier::class,
    TableHttpResponderInterface::class => TableHttpResponder::class,

    // Option A (default): plain JSON payload without data-response/content-negotiation.
    TablePayloadResponderInterface::class => JsonTablePayloadResponder::class,

    // Option B: integrate with yiisoft/data-response (ContentNegotiator profile).
    // TablePayloadResponderInterface::class => [
    //     'class' => Mheads\Yii\Table\Http\Response\Payload\DataResponseTablePayloadResponder,
    //     '__construct()' => [
    //         'dataResponseFactory' => \Yiisoft\Definitions\Reference::to(
    //             \Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface::class
    //         ),
    //     ],
    // ],

    // Option C: your custom responder implementation.
    // TablePayloadResponderInterface::class => App\Table\MyPayloadResponder::class,

    // Optional serializers for split config/rows actions.
    //
    // Mheads\Yii\Table\Serialization\TableConfigSerializerInterface::class => Mheads\Yii\Table\Serialization\TableArraySerializer::class,
    // Mheads\Yii\Table\Serialization\TableRowsSerializerInterface::class => Mheads\Yii\Table\Serialization\TableArraySerializer::class,

    // Optional i18n wiring (uncomment relevant use/import lines in real app):
    // use Mheads\Yii\Table\I18n\TableTranslatorInterface;
    // use Mheads\Yii\Table\I18n\YiisoftTableTranslatorAdapter;
    // use Yiisoft\Translator\CategorySource;
    // use Yiisoft\Translator\Message\Php\MessageSource;
    // use Yiisoft\Translator\MessageFormatterInterface;
    //
    // TableTranslatorInterface::class => YiisoftTableTranslatorAdapter::class,
    // 'translation.mheads.table' => [
    //     'definition' => static function (\Yiisoft\Aliases\Aliases $aliases, MessageFormatterInterface $formatter) {
	//            $source = new MessageSource($aliases->get('@vendor/mheads/yii-table/resources/messages'));
	//            return new CategorySource(
	//                TableTranslatorInterface::CATEGORY,
	//                $source,
	//                $formatter,
	//            );
	//        },
	//        'tags'       => ['translation.categorySource'],
    // ],
];
