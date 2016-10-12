<?php
include 'Config.php';
class Server {
	private $serv;

	public function __construct() {
		$this->serv = new swoole_server("0.0.0.0", 9501);
		$this->serv->set(array(
			'worker_num' => 8,
			'daemonize' => false,
			'max_request' => 10000,
			'dispatch_mode' => 2,
			'debug_mode' => 1,
			'task_worker_num' => 8,
		));

		$this->serv->on('Start', array($this, 'onStart'));
		$this->serv->on('Connect', array($this, 'onConnect'));
		$this->serv->on('Receive', array($this, 'onReceive'));
		$this->serv->on('Close', array($this, 'onClose'));
		$this->serv->on('Task', array($this, 'onTask'));
		$this->serv->on('Finish', array($this, 'onFinish'));

		$this->serv->start();
	}

	public function onStart($serv) {
		echo "Start\n";
	}

	public function onConnect($serv, $fd, $from_id) {
		//$serv->send($fd, "Hello {$fd}!");
		echo "{$fd} is connected";
	}

	public function onReceive(swoole_server $serv, $fd, $from_id, $data) {
		echo "Get Message From Client {$fd}:{$data}\n";

		$serv->send($fd, "Hello");
		$param = array('fd' => $fd);
		//redis 异步处理
		$serv->task($data);
		//echo "Continue Handle Worker\n";
	}

	public function onClose($serv, $fd, $from_id) {
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
		var_dump($data);
		$type = $data["type"];
		$para_array = $data["para_array"];
		$para_array = (array) $para_array;
		if ($type == "String") {
			$this->redis_update($type, $para_array);
		} else {
			$key = $data["key"];
			$this->redis_update($type, $para_array, $key);
		}

		return "Task {$task_id}'s result:";
	}
	public function onFinish($serv, $task_id, $data) {
		echo "Task {$task_id} finish \n";
		echo "Result:{$data}\n";

	}
	private function redis_update($type = "String", $para_array = array(), $key = null) {
		$redis = new Redis();
		$redis->connect("127.0.0.1", "6379");
		$redis->select(REDIS_DB);
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