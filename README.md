# QueryList-Coroutine
QueryList Plugin: Coroutine

## Installation
```
composer require jaeger/querylist-coroutine
```

## API
- Coroutine **coroutine(int $size=0)**:use Coroutine QueryList.

class **Coroutine**:
- Coroutine **size($size)**:set max parallel num.
- Coroutine **add(string $option)**：add option.
- Coroutine **success(callable $callable)**: success callback.
- Coroutine **wait($post)**: wait for request.

## Usage
- Bind Coroutine

```php
use QL\QueryList;
use QL\Ext\Coroutine;

$ql = new QueryList();
$ql->use(Coroutine::class);
//or Custom function name
$ql->use(Coroutine::class,'coroutine');
```
- Your Rule like this

```php
$rule = [
    'package' => ['.col-sm-9>h4>a','text'],
    'link' => ['.col-sm-9>h4>a','href'],
    'desc'=>['.col-sm-9>p:last','text'],
    'language'=>['.col-sm-9>p:eq(0)','text'],
    'star'=>['.col-sm-3 span:eq(1)','text'],
];
$range = "li .row";
```

- Use Coroutine

```php
$coroutine = $ql->range($range)->rule($rule)->coroutine();
$coroutine->add("https://packagist.org/explore/popular");
$data = $coroutine->wait();
print_r($data->all());
```

- Use Coroutine with success callback

```php
$coroutine = $ql->range($range)->rule($rule)->coroutine();
$coroutine->add("https://packagist.org/explore/popular");
$coroutine->success(function($item){
    $item["star"]  = preg_replace('/\D/s', '', $item["star"] );
    return $item;
});
$data = $coroutine->wait();
print_r($data->all());
```

- Use Coroutine with size

```php
$coroutine = $ql->range($range)->rule($rule)->coroutine(1000);
$range = range(1,10000);
foreach($range as $i){
    $coroutine->add("https://packagist.org/explore/popular?page=".$i);
}
$data = $coroutine->wait();
print_r($data->all());
```

Out:

```
Array
(
     [0] => Array
        (
            [package] => symfony/polyfill-mbstring
            [link] => /packages/symfony/polyfill-mbstring
            [desc] => Symfony polyfill for the Mbstring extension
            [language] => PHP
            [star] => 7373
        )

    [1] => Array
        (
            [package] => psr/log
            [link] => /packages/psr/log
            [desc] => Common interface for logging libraries
            [language] => PHP
            [star] => 9976
        )
	...
)

```

