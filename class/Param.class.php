<?php


/**
 *
 * @author rubens
 *        
 */
class Param
{

	private $name;
	private $value;
	
	const PHONE_DDD = 'ddd';
	const PHONE_PREFIX = 'prefixo';
	const PHONE_END = 'final';
	const PHONE_NUMBER = 'numero';
	const PHONE_FULL = 'completo';
	
	/**
	 */
	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}
	
	public function hasValue()
	{
		return !empty($this->value);
	}

	/**
	 * 
	 * @return number
	 */
	public function length(){
		return strlen($this->value);
	} 
	
	/**
	 * 
	 * @return mixed
	 */
	public function asString()
	{
		return (string)$this->value;
	}
	
	public function asScapedString()
	{
		return DB::escape((string)$this->value);
	}
	
	
	
	/**
	 * 
	 * @return mixed
	 */
	public function get()
	{
		return $this->value;
	}
	
	/**
	 * 
	 * @return bool
	 */
	public function asBool()
	{
		return (bool)$this->value;
	}
	
	/**
	 * 
	 * @return int
	 */
	public function asInt()
	{
		return (int)$this->value;
	}
	
	public function asDate()
	{
		return date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $this->value)));
	}
	
	public function asFloat()
	{
		return (float)str_replace(array('.', ','), array('', '.'), $this->value);
	}
	
	
	/**
	 * 
	 * @return bool|string
	 */
	public function isEmail()
	{
		return Util::isValideEmail($this->value)?$this->value:false;
	}
	
	/**
	 * Verifica se é um telefone brasileiro, se o parâmetro por passado como true, retorna um array com o número do
	 * DDD no índice ddd e o resto no índice numero
	 * @param string $return_array
	 * @return boolean|array
	 */
	public function isBrPhoneNumber($return_array = false)
	{
		$ddd = '(?<'.self::PHONE_DDD.'>\(\d\d\)\s?|\d\d\s*)';
		$prefixo = '(?<'.self::PHONE_PREFIX.'>\d{4,5})';//aceita 5 dígito por que alguns estados já tem 9 dígitos
		$parte_final = '(?<'.self::PHONE_END.'>\d{4})';
		$regex = $ddd.'\s?(?<'.self::PHONE_NUMBER.'>'.$prefixo.'[-\s]?'.$parte_final.")";
		/*
		 * Deve aceitar APENAS:
$teste[] = '(31) 92343109';
$teste[] = '(31) 9234-3109';
$teste[] = '(31) 9234 3109';
$teste[] = '(31)92343109';
$teste[] = '(31)9234-3109';
$teste[] = '(31)9234 3109';
$teste[] = '31 92343109';
$teste[] = '31 9234-3109';
$teste[] = '31 9234 3109';
foreach ($teste as $value) 
	echo $value.": ".( preg_match("/(?<completo>$regex)/", $value)?"Aceitou\n":"Negou\n" ) ;
		 */
		if(preg_match("/(?<".self::PHONE_FULL.">$regex)/", $this->value, $array))
		{
			if($return_array)
			{
				$return[self::PHONE_DDD] =  preg_replace('/[^0-9]/', '', $array[self::PHONE_DDD]);
				$return[self::PHONE_PREFIX] = $array[self::PHONE_PREFIX];
				$return[self::PHONE_END] = $array[self::PHONE_END];
				$return[self::PHONE_NUMBER] = $array[self::PHONE_PREFIX].$array[self::PHONE_END];
				$return[self::PHONE_FULL] = '('.$return[self::PHONE_DDD].') '.$array[self::PHONE_PREFIX].'-'.$array[self::PHONE_END];
				return $return;
			}
			else 
				return $this->value;
		}
		else 
			return false;
	}
	
	
	public function isNumeric()
	{
		return is_numeric($this->value);
	}
	public function checkRegex($regex)
	{
		preg_match($regex, $this->value, $return);
		return $return;
	}
	
	public function isBrCPF()
	{
		if(Util::isValideBrCPF($this->value))
			return preg_replace('/[^0-9]/', '', $this->value);
		else
			return false;
	}

	public function __toString(){
		return $this->asString();
	}
	
}


