# Custom payload contract

Default non-export table payload is produced by `TableArraySerializer`.

Implement `TableSerializerInterface` when the frontend needs a different payload shape.
The serializer contract only requires an array; the top-level `{config, pagination, columns, filters, sorts, rows}` shape is specific to the default `TableArraySerializer`.

`TableArraySerializer` also implements smaller serializers for applications that split table metadata from row loading:

- `TableConfigSerializerInterface::serializeConfig()` returns `config`, `columns`, `filters`, and `sorts`.
- `TableRowsSerializerInterface::serializeRows()` returns `pagination` and `rows`.

The library does not add separate endpoints for this mode. Use these interfaces in application actions when config and rows are requested separately. Apply the request to the table before `serializeConfig()` when filter payloads should include request values.

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
