<?php
include '../vendor/autoload.php';
include 'GPBMetadata/Hello.php';
include 'Lm/Hello.php';

$from = new \Lm\hello();
$from->setId(1);
$from->setStr('foo bar, this is a message');
$from->setOpt(29);
$data = $from->serializeToString();
file_put_contents('data.bin', $data);


$data = file_get_contents('data.bin');
$to = new \Lm\hello();
$to->mergeFromString($data);
echo $to->getId() . PHP_EOL;
echo $to->getStr() . PHP_EOL;
echo $to->getOpt() . PHP_EOL;
