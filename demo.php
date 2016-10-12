<?php
/**
 * 测试方法
 * @author : harry
 * @UserFunction(method=GET)
 * @return                    [type] [description]
 */
function test() {
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
	$rc->connect();
	$msg = json_encode($data);
	$rc->send($msg);
	//it's end and then continued execute other code;
	echo "success";
}