<?php
include 'Config.php';
class Server {
	private $serv;

	public function __construct() {
		$this->serv = new swoole_server("0.0.0.0", 9501);
		$this->serv->set(array(
			/**
			 * 设置启动的worker进程数。
			 *	业务代码是全异步非阻塞的，这里设置为CPU的1-4倍最合理
			 *	业务代码为同步阻塞，需要根据请求响应时间和系统负载来调整
			 *	比如1个请求耗时100ms，要提供1000QPS的处理能力，那必须配置100个进程或更多。但开的进程越多，占用的内存就会大大增加，而且进程间切换的开销就会越来越大。所以这里适当即可。不要配置过大。
			 *	每个进程占用40M内存，那100个进程就需要占用4G内存
			 */
			'worker_num' => 8,
			'daemonize' => false,
			'dispatch_mode' => 3,
			'max_request' => 10000,
			'debug_mode' => 1,
			'task_worker_num' => 8,
		));
		//注册Server的事件回调函数。
		//主进程的回调函数
		$this->serv->on('Start', array($this, 'onStart'));
		//以下四个是 work 进程的回调函数
		//服务器启动成功后，onStart/onManagerStart/onWorkerStart会在不同的进程内并发执行
		//onReceive/onConnect/onClose/onTimer在worker进程(包括task进程)中各自触发
		$this->serv->on('WorkerStart', array($this, 'onWorkerStart'));
		$this->serv->on('Connect', array($this, 'onConnect'));
		$this->serv->on('Receive', array($this, 'onReceive'));
		$this->serv->on('Close', array($this, 'onClose'));
		//task work 进程，接受由Worker进程通过swoole_server->task/taskwait方法投递的任务
		//onTask事件仅在task进程中发生
		$this->serv->on('Task', array($this, 'onTask'));
		//task work 进程，任务完成执行的回调函数
		$this->serv->on('Finish', array($this, 'onFinish'));

		//所有事件回调均在$server->start后发生
		$this->serv->start();
	}

	public function onStart($serv) {
		echo "Start\n";
	}
	public function onWorkerStart($serv, $worker_id) {
		if ($worker_id >= $serv->setting["worker_num"]) {
			$redis = new Redis();
			$redis->pconnect("127.0.0.1", 6379);
			$redis->select(REDIS_DB);
			$this->serv->redis = $redis;
		}

	}

	public function onConnect($serv, $fd, $from_id) {
		//$serv->send($fd, "Hello {$fd}!");
		echo "{$fd} is connected";
	}

	public function onReceive(swoole_server $serv, $fd, $from_id, $data) {
		echo "Get Message From Client {$fd}:{$data}\n";

		//$serv->send($fd, "Hello");
		$param = array('fd' => $fd);
		//redis 异步处理
		$serv->task($data);
		//echo "Continue Handle Worker\n";
	}

	public function onClose($serv, $fd, $from_id) {
		//$serv->redis->close();
		echo "Client {$fd} close connection\n";
	}
	public function onTask($serv, $task_id, $form_id, $data) {
		echo "This Task {$task_id} from Worker {$form_id}\n";
		// for ($i = 0; $i < 10; $i++) {
		// 	sleep(1);
		// 	echo "Task {$task_id} Handle {$i} times...\n";
		// }
		//$fd = json_decode($data, true)['fd'];
		//$serv->send($fd, "Data in Task {$task_id}");
		$data = json_decode(json_encode($data), true);
		$data = (array) json_decode($data);
		//var_dump($data);
		$type = $data["type"];
		$para_array = $data["para_array"];
		$para_array = (array) $para_array;
		if ($type == "String") {
			$this->redis_update($serv->redis, $type, $para_array);
		} else {
			$key = $data["key"];
			$this->redis_update($serv->redis, $type, $para_array, $key);
		}

		return "Task {$task_id}'s result:";
	}
	public function onFinish($serv, $task_id, $data) {
		//$serv->redis->close();		
		echo "Task {$task_id} finish \n";
		echo "Result:{$data}\n";

	}
	private function redis_update($redis, $type = "String", $para_array = array(), $key = null) {
		// $redis = new Redis();
		// $redis->connect("127.0.0.1", "6379");
		// $redis->select(REDIS_DB);
		sleep(2);
		switch ($type) {
		case 'String':
			echo $redis->mset($para_array);
			break;
		case 'Hash':
			$redis->hmset($key, $para_array);
			break;
		default:
			return;
			break;
		}

	}
}
// 启动服务器
$server = new Server();

?>
