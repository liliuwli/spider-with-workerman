<?php
	use Workerman\Worker;
	use Workerman\Lib\Timer;
	require_once './Workerman/Autoloader.php';
	require_once './Spider/Untils/until.php';
	
	$http_worker = new Worker('text://127.0.0.1:2345');

	$http_worker->count = 40;
	$http_worker->onWorkerStart = function($worker)
	{
		$worker->driver = new \Spider\Parser\zhihuSpider();
		$time_interval = 2;
		$flag = ($worker->id + 1)%4==0?TRUE:FALSE;
		Timer::add($time_interval,array($worker->driver,'run'),array($flag,$worker->id));
	};
	
	$http_worker->onMessage = function($connection, $data)
	{

	};
	Worker::runAll();
	
