<?php

/**
 *
 * @author rubens
 *        
 */
class JResponse
{
	/**
	 * 
	 * @var JResponse
	 */
	private static $instance;
	private $data = array();
	private $errors = array();
	private $types = array(self::RESULT_SUCESS, self::RESULT_F_AUTH, self::RESULT_F_ERROR, self::RESULT_F_FAIL, self::RESULT_F_PERMISSION);
	
	const RESULT_SUCESS = 'sucesso';
	const RESULT_F_AUTH = 'login';
	const RESULT_F_FAIL = 'fail';//erro do sistema (exceção)
	const RESULT_F_ERROR = 'erro';//erro do usuário
	const RESULT_F_PERMISSION = 'permission';//falta de permissão
	
	
	
	/**
	 */
	private function __construct()
	{
	}
	
	public static function start()
	{
		if(isset(self::$instance))
			throw new MyException('Já hávia uma instância do JResponse');
		self::$instance = new self();
		return self::$instance; 
	}

	public static function addData($name, $value)
	{
		self::$instance->data[$name] = is_object($value) ?  var_export($value, true) : $value;
		return self;
	}
	
	public static function addErro($message)
	{
		self::$instance->errors[] = $message;
		return self;
	}
	
	public static function hasError()
	{
		return count(self::$instance->errors);
	}
	
	public static function send($type,$title='', $message = '', $data = array())
	{
		self::$instance->data = array_merge(self::$instance->data, $data);
		if(!in_array($type, self::$instance->types))
			throw new MyException('Por favor passe um tipo conhecido para o JResponse');
		if(SYS_MODO_DEVEL && $type == self::RESULT_F_FAIL){
			$r['debug'] = DB::error();
		}
		$r['result'] = $type;//erro/fail/auth/permission
		$r['title'] = $title;
		$r['message'] = $message;
		$r['data'] = self::$instance->data;
		$r['errors'] = self::$instance->errors;
		header("Content-type: text/json");
		exit(json_encode($r));
	}
}


