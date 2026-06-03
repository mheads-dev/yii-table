# Custom payload contract

Default non-export table payload is produced by `TableArraySerializer`.

Implement `TableSerializerInterface` when the frontend needs a different payload shape.

## DI override

```php
use Mheads\Yii\Table\Http\Response\TableHttpResponder;
use Mheads\Yii\Table\Http\Response\TableHttpResponderInterface;
use Yiisoft\Definitions\Reference;

return [
    TableHttpResponderInterface::class => [
        'class' => TableHttpResponder::class,
        '__construct()' => [
            'serializer' => Reference::to(App\Table\MyTableSerializer::class),
        ],
    ],
];
```

## Payload responder

Implement `TablePayloadResponderInterface` when serializer output is acceptable, but response creation should use an application-specific response factory or envelope.

- [Configuration](configuration.md)
