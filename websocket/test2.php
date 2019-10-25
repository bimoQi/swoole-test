<?php
go(function () {

    $client = new Swoole\Coroutine\Http\Client("th5wss.jjhgame.com", 443, 1);

    $client->set([
        'keep_alive' => true,
    ]);
    $client->setHeaders([
        'Host' => 'th5wss.jjhgame.com',
        'UserAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36',
    ]);

    $client->upgrade('/stage_server?access=f241c54c27bb4b86856f13fcc634ff51&login=1&flag=1571896362841');

    $timer_id = swoole_timer_tick(3000, function ($timer_id) use ($client) {
        $data = ['id' => 11001, 'a' => null]; //心跳
        $str = json_encode($data);
        $client->push($str);
    });

    $timer_id2 = swoole_timer_tick(3000, function ($timer_id2) use ($client) {
        static $hit_id = 1;
        echo 'hit_id:' . $hit_id . PHP_EOL;
        $hit_id++;
        $data = [
            'id' => 12035,
            'a' => [
                'hit' => [
                    [
                        'b' => $hit_id,
                        'f' => [mt_rand(10, 500)],
                    ],
                ],
            ],
        ];
        $str = json_encode($data);
        $client->push($str);
        var_dump($client->recv());
    });
});
