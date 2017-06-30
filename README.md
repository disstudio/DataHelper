# DataHelper
Helper class that validates and converts complex data structures

# Usage
Just call the function CDataHelper::getData(mixed $your_data, array $attributes)  
**Note: if you want to process an array with attribute values, cast it to an object to prevent initial iteration.**

## Attributes syntax
Each element in attributes array has the following syntax:  
**name \[as alias\] \[type\]**  
**name:** Attribute name to retrieve from source array or object. You may access nested parameters separating by dot (e.g. 'item.id')  
**alias:** Output attribute with another name.  
**type:** Cast value to specified type. Supported types: int, float, array. Array type works only if attribute value is an array.

Setting a dot for attribute name or alias has e special meaning.
Dot as attribute name returns object/array itself instead of specified attribute.
Dot as alias name appends value or all sub-elements to current level instead of adding them accordingly.
Setting '%key%' as name attribute will return current item key in array.

## Nested attributes
You may specify nested attributes by the following syntax:  
**'attribute_name' => ['sub_attr_1', 'sub_attr_2'];**  
Sub-attributes have the same syntax as above.

You may also use anonymous function as nested attribute.  
**'attribute_name' => function($data) { return strlen($data); }**  
Above we send attribute value into function. You may send object itself:  
**'. as attribute_name' => function($data) ...**  
And you may append several attributes with a function:  
**'. as .' => function($data) \{ return \['foo' => 'bar', 'foe' => 'baz'\]; \}**

# Examples

## Some tricks with arrays
Source data
```php
$ar_response = [
    'groups' => [
        [ 'id' => '100', 'title' => 'Client' ],
        [ 'id' => '101', 'title' => 'Manager' ],
        [ 'id' => '102', 'title' => 'Administrator' ],
    ]
];
```

Process:
```php
$data = CDataHelper::getData((object) $ar_response, [
    'groups array' => [
        'id int', 'title'
    ]
]);
echo json_encode($data, JSON_PRETTY_PRINT);
```

will result:
```json
{
    "groups": [
        {
            "id": 100,
            "title": "Client"
        },
        {
            "id": 101,
            "title": "Manager"
        },
        {
            "id": 102,
            "title": "Administrator"
        }
    ]
}
```
String IDs were casted to int.

Bring array on top level by dot alias:
```php
$data = CDataHelper::getData((object) $ar_response, [
    'groups as . array' => [
        'id int', 'title'
    ]
]);
echo json_encode($data, JSON_PRETTY_PRINT);
```

Result:
```json
[
    {
        "id": 100,
        "title": "Client"
    },
    {
        "id": 101,
        "title": "Manager"
    },
    {
        "id": 102,
        "title": "Administrator"
    }
]
```

## Process Google geocoder answer
```json
{
   "results" : [
      {
         "address_components" : [
            {
               "long_name" : "1600",
               "short_name" : "1600",
               "types" : [ "street_number" ]
            },
            {
               "long_name" : "Amphitheatre Pkwy",
               "short_name" : "Amphitheatre Pkwy",
               "types" : [ "route" ]
            },
            {
               "long_name" : "Mountain View",
               "short_name" : "Mountain View",
               "types" : [ "locality", "political" ]
            },
            {
               "long_name" : "Santa Clara County",
               "short_name" : "Santa Clara County",
               "types" : [ "administrative_area_level_2", "political" ]
            },
            {
               "long_name" : "California",
               "short_name" : "CA",
               "types" : [ "administrative_area_level_1", "political" ]
            },
            {
               "long_name" : "United States",
               "short_name" : "US",
               "types" : [ "country", "political" ]
            },
            {
               "long_name" : "94043",
               "short_name" : "94043",
               "types" : [ "postal_code" ]
            }
         ],
         "formatted_address" : "1600 Amphitheatre Parkway, Mountain View, CA 94043, USA",
         "geometry" : {
            "location" : {
               "lat" : 37.4224764,
               "lng" : -122.0842499
            },
            "location_type" : "ROOFTOP",
            "viewport" : {
               "northeast" : {
                  "lat" : 37.4238253802915,
                  "lng" : -122.0829009197085
               },
               "southwest" : {
                  "lat" : 37.4211274197085,
                  "lng" : -122.0855988802915
               }
            }
         },
         "place_id" : "ChIJ2eUgeAK6j4ARbn5u_wAGqWA",
         "types" : [ "street_address" ]
      }
   ],
   "status" : "OK"
}
```

```php
$ar_response = json_decode($json, true);
if($ar_response['status']=='OK') {
    $data = CDataHelper::getData($ar_response['results'], [
        'address_components as full_address' => function($data) {
            array_walk($data, function(&$item) {
                $item = "{$item['types'][0]}: {$item['short_name']}";
            });
            return implode('; ', $data);
        },
        'formatted_address as text',
        'geometry.location as .' => [
            'lat as latitude float',
            'lng as longitude float'
        ]
    ]);
    echo json_encode($data, JSON_PRETTY_PRINT);
}
```

will output:
```json
[
    {
        "full_address": "street_number: 1600; route: Amphitheatre Pkwy; locality: Mountain View; administrative_area_level_2: Santa Clara County; administrative_area_level_1: CA; country: US; postal_code: 94043",
        "text": "1600 Amphitheatre Parkway, Mountain View, CA 94043, USA",
        "latitude": 37.4224764,
        "longitude": -122.0842499
    }
]
```
