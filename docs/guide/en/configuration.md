# Configuration

## Minimal HTTP setup

```php
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
    TablePayloadResponderInterface::class => JsonTablePayloadResponder::class,
];
```

Full example with optional responders and i18n notes:

- [examples/di/common.php](../../../examples/di/common.php)

## Payload responder options

Use `JsonTablePayloadResponder` when you want a plain PSR-7 JSON response.

Use `DataResponseTablePayloadResponder` when the application already formats responses through `yiisoft/data-response` and content negotiation.

Implement `TablePayloadResponderInterface` when the application has its own response contract.

## I18n

For translated filter labels, bind `TableTranslatorInterface` to `YiisoftTableTranslatorAdapter` and register a translation category source.

- [I18n](i18n.md)
