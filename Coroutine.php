<?php


declare(strict_types=1);

namespace QL\Ext;

use QL\Contracts\PluginContract;
use QL\QueryList;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\WaitGroup;
use Tightenco\Collect\Support\Arr;
use Tightenco\Collect\Support\Collection;

class Coroutine implements PluginContract
{
    protected QueryList $ql;
    protected $size;
    protected $options = [];
    protected $successCallback;
    protected $pool;

    public function __construct(QueryList $ql, $size=500)
    {
        $this->ql = $ql;
        $this->size = $size;
    }
    public static function install(QueryList $queryList, ...$opt)
    {
        $queryList->bind("coroutine",function (){
            return new Coroutine($this);
        });
    }
    public function size($size)
    {
        $this->size = $size;
    }
    public function add($option)
    {
        $this->options[] = $option;
    }
    public function success(callable $callback)
    {
        $this->successCallback = $callback;
    }
    public function wait($post=false)
    {
        $method = $post ? "post" : "get";
        $result = new Collection();
        $channel = new Channel($this->size);
        $wg = new WaitGroup();
        $wg->add(count($this->options));
        while ($option = array_shift($this->options)) {
            $channel->push(true);
            go(function () use ($option, $channel,$method, $wg, &$result, &$throwables) {
                try {
                    $option = Arr::wrap($option);
                    [$url, $data] = count($option) === 1 ? [$option[0], []] : $option;
                    $data = $this->ql->$method($url,$data)->query()->getData($this->successCallback);
                    $result = $result->merge($data);
                    QueryList::destructDocuments();
                } finally {
                    $channel->pop();
                    $wg->done();
                }
            });
        }
        $wg->wait();
        return $result;
    }
}
