<?php
/**
 * 测试方法
 * @author : harry
 * @UserFunction(method=GET)
 * @return                    [type] [description]
 */
function test() {
	setcookie("TIME", time());
	//input redis type String
	// $type = "String";
	// $key = "TEST";
	// $val = "TESTVALS";
	// $data = array();
	// $data["type"] = $type;
	// $data["key"] = $key;
	// $data["para_array"] = array($key => $val);
	// $rc = new RedisClient();
	// $rc->connect();
	// $msg = json_encode($data);
	// $rc->send($msg);
	//it's end and then continued execute other code;
	//echo "success";
	// ---------------------------------------------------------------------
	//input redis type Hash
	$type = "Hash";
	$key = "Hash";
	$filed = "hash_field";
	$val = "hash_val";
	$data["type"] = $type;
	$data["key"] = $key;
	$data["para_array"] = array($filed => $val);
	$rc = new RedisClient();
	echo time();
        $msg = json_encode($data);
	$rc->connect();
	
	$rc->send($msg);

	//it's end and then continued execute other code;
	echo "success";
}

/**
 * 测试常规方法
 * @author : harry
 * @Tpl(method=GET)
 * @return                    [type] [description]
 */
function test_common() {
	//input redis type String
	setcookie("TIME", time());
	$type = "String";
	$starttime = time();
	echo "starttime:" . $starttime . "</br>";
	for ($i = 0; $i < 10; $i++) {
		# code...
		sleep(2);
		// $key = $i;
		$val = "TESTVALS";
		$redis = new Redis();
		$redis->connect("127.0.0.1", 6379);
		$redis->select(4);
		$key = $i;
		$redis->set($i, $val);
		$redis->close();

	}
	$endtime = time();
	echo "endtime:" . $endtime . "</br>";
	echo "sumtime:" . (int) ($endtime - $starttime);
	//it's end and then continued execute other code;
	//echo "success";
}
/**
 * 测试服务方法
 * @author : harry
 * @Tpl(method=GET)
 * @return                    [type] [description]
 */
function test_server() {
	setcookie("TIME", time());
	$type = "String";
	$starttime = time();
	echo "starttime:" . $starttime . "</br>";
	for ($i = 0; $i < 10; $i++) {
		# code...
		$key = $i;
		$val = "TESTVALS";
		$data = array();
		$data["type"] = $type;
		$data["key"] = $key;
		$data["para_array"] = array($key => $val);
		$rc = new RedisClient();
		$msg = json_encode($data);		
		$rc->connect();
		$rc->send($msg);
	}
	$endtime = time();
	echo "endtime:" . $endtime . "</br>";
	echo "sumtime:" . (int) ($endtime - $starttime);

}
/**
 * 测试服务方法
 * @author : harry
 * @Tpl(method=GET)
 * @return                    [type] [description]
 */
function press_test_common() {
	setcookie("TIME", time());
	$type = "String";
	$val = "TESTVALS";
	$key = "YH" . mt_rand(0, 100000);
	$redis = new Redis();
	$redis->connect("127.0.0.1", 6379);
	$redis->select(4);
	$redis->set($key, $val);
	sleep(2);
	echo "success";
}
/**
 * 测试服务方法
 * @author : harry
 * @Tpl(method=GET)
 * @return                    [type] [description]
 */
function press_test_server() {
	setcookie("TIME", time());
	$type = "String";
	$key = "YH" . mt_rand(0, 100000);
	$val = "TESTVALS";
	$data = array();
	$data["type"] = $type;
	$data["key"] = $key;
	$data["para_array"] = array($key => $val);
	$rc = new RedisClient();
	$rc->connect();
	$msg = json_encode($data);
	$rc->send($msg);
	echo "success";
}
/**
 * 删除keys
 * @author : harry
 * @Tpl(method=GET)
 * @return                    [type] [description]
 */
function del() {
	setcookie("TIME", time());
	$redis = new Redis();
	$redis->connect("127.0.0.1", 6379);
	$redis->select(3);
	for ($i = 0; $i < 1000; $i++) {
		$redis->del($i);
	}
	echo "success";
}
