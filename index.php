<?php
use App\Models\RsaCrypt;
include 'EasyDB.php'; //数据操作类
//include('DBUtil.php'); //数据操作类
include 'ProException.php'; //执行异常
include 'Config.php'; //基本配置文件
include 'Annotation.php'; //标注
include 'UserAnnotation.php'; //标注
//include 'UserProcess.php'; //逻辑处理文件, 注：业务流程处理示例参看此文件
//include('HelperProcess.php');
include "demo.php";
include './lib/RedisClient.php';

//查找方法标注
function getAnnotation(Reflector $ref) {
	$doc = $ref->getDocComment();
	$annotations = array();
	if ($doc !== false) {
		$pattern = '/@\s*(\w+)\s*(?:\((.+)\))?/i';
		if (preg_match($pattern, $doc)) {
			preg_match_all($pattern, $doc, $annotation_matches);
			for ($i = 0; $i < count($annotation_matches[0]); $i++) {
				if (class_exists($annotation_matches[1][$i])) {
					$_class = new $annotation_matches[1][$i]();
					if ($_class instanceof Annotation) {
						$annotations[$annotation_matches[1][$i]] = $_class;
						if (!empty($annotation_matches[2][$i]) && preg_match('/^(?:\s*\w+\s*=\s*\w+\s*,?)+$/i', $annotation_matches[2][$i])) {
							preg_match_all('/(\w+)\s*=\s*(\w+)\s*,?/i', $annotation_matches[2][$i], $annotation_param_matches);
							for ($j = 0; $j < count($annotation_param_matches[0]); $j++) {
								$_property = $annotation_param_matches[1][$j];
								if (property_exists($_class, $_property)) {
									$_class->$_property = $annotation_param_matches[2][$j];
								}
							}
						}
					}
				}
			}
		}
	}
	return $annotations;
}

//获取function名称
if (preg_match("/\w+(?:$|(?=\?))/", $_SERVER["REQUEST_URI"], $matches)) {
	$func = $matches[0];
}
//查询function，如果有则不执行，如果没有此function，需进行解密
if (!function_exists($func)) {
	$preg_pattern = '/index.php\/\S{0,256}/';
	$url = $_SERVER['REQUEST_URI'];
	preg_match($preg_pattern, $url, $result);
	$encode = str_replace("index.php/", "", $result[0]);
	if (!checkFunc($encode)) {
		die("API used");
	}
	setFuncStatus($encode);
	$encode = base64_decode($encode);
	$decode = RsaCrypt::decode($encode);
	$preg_time = '/\d*$/';
	preg_match($preg_time, $decode, $time);
	$time = $time[0];
	//时间限制，如果时间间隔大于10分钟，则说明可能是抓包访问
	if (time() - $time > 600) {
		die("API overtime");
	}
	$func = str_replace($time, "", $decode);
	$func = str_replace("index.php/", "", $func);
	if (!function_exists($func)) {
		header("status: 404 Not Found");
		die("Missing $func Function.");
	}
}
//执行function
if (isset($func)) {
	$obj_ref = new ReflectionFunction($func);
	$params = array();
	//参数填充
	foreach ($obj_ref->getParameters() as $param) {
		$param_key = $param->getName();
		if ($param->getClass() != null) {
			$param_class_name = $param->getClass()->getName();
			if (is_subclass_of($param_class_name, 'BaseType')) {
				if (isset($_REQUEST[$param_key])) {
					$param_class = new $param_class_name($_REQUEST[$param_key]);
					array_push($params, $param_class);
				} elseif ($param->isDefaultValueAvailable()) {
					array_push($params, $param->getDefaultValue());
				} else {
					header("status: 403 Forbidden");
					die("Missing $param_key parameter.");
				}
			} else {
				$param_class = new $param_class_name();
				foreach ($_REQUEST as $key => $value) {
					$_method_name = 'set' . $key;
					if (method_exists($param_class, $_method_name)) {
						$_method_ref = new ReflectionMethod($param_class, $_method_name);
						if ($_method_ref->isPublic() && !$_method_ref->isStatic() && !$_method_ref->isAbstract()) {
							$_method_params_ref = $_method_ref->getParameters();
							if (count($_method_params_ref) == 1) {
								if ($_method_params_ref[0]->getClass() != null) {
									$_method_params_class_name = $_method_params_ref[0]->getClass()->getName();
									$_method_params_class = new $_method_params_class_name($_REQUEST[$param_key]);
									$param_class->$_method_name($_method_params_class);
								} else {
									$param_class->$_method_name($value);
								}
							}
						}
					} elseif (property_exists($param_class, $key)) {
						$_property_ref = new ReflectionProperty($param_class, $key);
						if ($_property_ref->isPublic() && !$_property_ref->isStatic()) {
							$param_class->$key = $value;
						}
					}
				}
				array_push($params, $param_class);
			}
		} elseif (isset($_REQUEST[$param_key])) {
			array_push($params, $_REQUEST[$param_key]);
		} elseif ($param->isDefaultValueAvailable()) {
			array_push($params, $param->getDefaultValue());
		} else {
			header("status: 403 Forbidden");
			die("Missing $param_key parameter.");
		}
	}
	//查找方法标注
	$annotations = getAnnotation($obj_ref);
	//只允许执行标注UserFunction
	if (!array_key_exists('UserFunction', $annotations) && !array_key_exists('Tpl', $annotations)) {
		header("status: 404 Not Found");
		die("Not allowed to execute the $func ");

	}

	//执行function UserFunction 有json返回值，Tpl无返回值
	try {
		array_walk($annotations, function ($value) use ($params) {
			if ($value instanceof Dependency && method_exists($value, 'excuting')) {
				$value->excuting($params);
			}
		});
		$result = call_user_func_array($func, $params);
		header("Access-Control-Allow-Origin: *");
		if (array_key_exists('UserFunction', $annotations)) {
			if (isset($result)) {
				echo json_encode(array("code" => 200, "result" => $result));
			} else {
				echo json_encode(array("code" => 200));
			}
		}
		array_walk($annotations, function ($value) use ($params) {
			if ($value instanceof Dependency && method_exists($value, 'excuted')) {
				$value->excuted($params);
			}
		});
	} catch (ProException $pe) {
		die(json_encode(array("code" => $pe->getCode(), "message" => $pe->getMessage())));
	} catch (Exception $e) {
		header("status: 500 Error");
		die($e->getMessage());
	}
} else {
	//header("status: 404 Not Found");
	//die("Missing $func Function.");

}
