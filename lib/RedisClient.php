<?php
class RedisClient {
	private $rc;

	public function __construct() {
		$this->rc = new swoole_client(SWOOLE_SOCK_TCP);

	}

	public function connect() {
		if (!$this->rc->connect("127.0.0.1", 9501, 1)) {
			echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
		}
		// $message = $this->client->recv();
		// echo "Get Message From Server:{$message}\n";

		//fwrite(STDOUT, "请输入消息：");
		//$msg = trim(fgets(STDIN));

	}
	public function send($msg) {
		$this->rc->send($msg);
	}
	public function recv() {
		return $this->rc->recv();
	}
}

// $client = new Client();
// $client->connect();
// $msg = array("Test", "harry");
// $client->send(json_encode($msg));
// echo $client->recv();