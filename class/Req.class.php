<?php


/**
 *
 * @author rubens
 * @TODO Criar um método (ou talvez até uma outra classe) para validar dos dados vindo do frontend para garantir que não haverá erros ao inserir no banco (tamanho da entrada por exemplo)
 *        
 */
class Req
{
	private static $instance;
	private $post_param = array();
	private $get_param = array();
	private  $file_param = array();

	private function __construct()
	{
		$this->post_param = $_POST;
		$this->get_param = $_GET;
		
		if($_FILES)
		foreach ($_FILES as $name => $info)
		{
			if(is_array($info[ParamFile::NAME]))
			{
				foreach ($info[ParamFile::NAME] as $pos => $value)
				{
					$this->file_param[$name][$pos][ParamFile::NAME] = $_FILES[$name][ParamFile::NAME][$pos];
					$this->file_param[$name][$pos][ParamFile::TYPE] = $_FILES[$name][ParamFile::TYPE][$pos];
					$this->file_param[$name][$pos][ParamFile::SIZE] = $_FILES[$name][ParamFile::SIZE][$pos];
					$this->file_param[$name][$pos][ParamFile::TMP_NAME] = $_FILES[$name][ParamFile::TMP_NAME][$pos];
					$this->file_param[$name][$pos][ParamFile::ERROR] = $_FILES[$name][ParamFile::ERROR][$pos];
				}
			}else
				$this->file_param[$name] = $_FILES[$name];
		}
	}

	public static function start()
	{
		if (! is_null(self::$instance))
			throw new MyException('The RPost already was initialized');
		self::$instance = new self();
		return self::$instance;
	}
	
	/**
	 * 
	 * @param string $name Nome do parâmetro passado por POST
	 * @return multitype:Param|Param
	 */
	public static function post($name)
	{
		if(is_array(self::$instance->post_param[$name]))
		{
			$return = array();
			foreach (self::$instance->post_param[$name] as $index => $value)
				$return[$index] = new Param($name, $value);
			return $return;
		}
		else
			return new Param($name, self::$instance->post_param[$name]);
	}
	
	/**
	 *
	 * @param string $name Nome do parâmetro passado por GET
	 * @return Param
	 */
	public static function get($name)
	{
		return new Param($name, self::$instance->get_param[$name]);
	}
	
	/**
	 * 
	 * @param unknown $name
	 * @return multitype:ParamFile|ParamFile
	 */
	public static function file($name)
	{
// 		if(is_array(self::$instance->file_param[$name]))
// 		{
// 			$return = array();
// 			foreach (self::$instance->file_param[$name] as $file)
// 				$return[] = new ParamFile($name, $file);
// 			return $return;
// 		}
// 		else
		return new ParamFile($name, self::$instance->file_param[$name]);
	}

	public static function wasFileSent($name)
	{
		return isset(self::$instance->file_param[$name]);
	}
	
	public static function wasPostSent($name)
	{
		return isset(self::$instance->post_param[$name]);
	}
	
	public static function wasGetSent($name)
	{
		return isset(self::$instance->get_param[$name]);
	}

	public function __toString()
	{
		return json_encode(self::dump());
	}
	
	public static function dump() {
		return array('GET' => self::$instance->get_param, 'POST' => self::$instance->post_param, 'FILES' => self::$instance->file_param);
	}
	
	
	public static function instance()
	{
		return self::$instance;
	}
}


