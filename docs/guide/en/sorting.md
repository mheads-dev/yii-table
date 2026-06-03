# Sorting

Sorting can be declared on columns or as separate sort options.

Default sort query parameter: `sort`.

## Request example

```text
/products?sort=-name&per-page=10&page=2&filter[name]=Wireless%20Headphones
```

Where:

- `sort=-name` means descending by `name`;
- `per-page=10` sets page size;
- `page=2` selects second page;
- `filter[name]=...` applies `SearchFilter` by column filter key.

## Column sorting

A sortable column exposes two request values:

```text
/products?sort=name
/products?sort=-name
```

The serialized column payload contains sort state and request values:

```json
{
  "key": "name",
  "title": "Name",
  "sort": {
    "isSorted": true,
    "isDefault": false,
    "sortedDirection": "descend",
    "values": {
      "ascend": "name",
      "descend": "-name"
    }
  },
  "isHidden": false,
  "filterKey": "name",
  "extraFilterKeys": []
}
```

## Sort options

Use sort options when the UI should show named sorting presets such as `name_desc` and `name_asc`.

```text
/products?sort=name_desc
/products?sort=name_asc
```

Serialized sort options are exposed in the top-level `sorts` list, separately from `columns[*].sort`:

```json
{
  "sorts": [
    {
      "title": "Sorting by Z-A",
      "value": "name_desc",
      "isDefault": false,
      "isDisabled": false,
      "isSelected": false
    },
    {
      "title": "Sorting by A-Z",
      "value": "name_asc",
      "isDefault": false,
      "isDisabled": false,
      "isSelected": false
    }
  ]
}
```

Example:

- [examples/usage/product-list-table-factory.php](../../../examples/usage/product-list-table-factory.php)
