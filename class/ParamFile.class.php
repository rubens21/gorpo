<?php


/**
 *
 * @author rubens
 *        
 */
class ParamFile
{
	const NAME = 'name';
	const TYPE = 'type';
	const SIZE = 'size';
	const TMP_NAME = 'tmp_name';
	const ERROR = 'error';

	private $name;
	private $info;
	
	/**
	 */
	public function __construct($name, $info)
	{
		$this->name = $name;
		$this->info = $info;
// 		$_FILES[$this->name][self::NAME] = $value[self::NAME];
// 		$_FILES[$this->name][self::TYPE] = $value[self::TYPE];
// 		$_FILES[$this->name][self::SIZE] = $value[self::SIZE];
// 		$_FILES[$this->name][self::TMP_NAME] = $value[self::TMP_NAME];
// 		$_FILES[$this->name][self::ERROR] = $value[self::ERROR];
		
	}
	
	public function wasSent()
	{
		return !empty($this->info);
	}
	
	
	
	public function save($path, $name = null){
		move_uploaded_file ($this->info[self::TMP_NAME], $path.($name?:$this->info[self::NAME]));
	}
	
	public function getSize()
	{
		return $this->info[self::SIZE];
	}
	
	public function getType()
	{
		return $this->info[self::TYPE];
	}
	
	public function getTmpName()
	{
		return $this->info[self::TMP_NAME];
	}
	public function getName()
	{
		return $this->info[self::NAME];
	}
	
	public function getExtension()
	{
		return pathinfo($this->info[self::NAME], PATHINFO_FILENAME);
	}
	
	public function isImage(){
		return Image::isImageAcceptable($this->info[self::TMP_NAME]);
	}
	/**
	 * 
	 * @return mixed
	 */
	public function get()
	{
		return $this->info;
	}
	
	public function getErrorMsg()
	{
		switch ($this->info[self::ERROR])
		{
			case UPLOAD_ERR_OK:
			//Value: 0; There is no error, the file uploaded with success.
				return false;
			break;
			case UPLOAD_ERR_INI_SIZE:
			//Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
				return 'O arquivo é maior do que o limite permitido pelo servidor ('.ini_get('upload_max_filesize').').';
			break;
			case UPLOAD_ERR_FORM_SIZE:
			//Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
				return 'O arquivo é maior do que o limite permitido pelo formulário.';
			break;
			case UPLOAD_ERR_PARTIAL:
			//Value: 3; The uploaded file was only partially uploaded.
				return 'O arquivo não foi enviado completamente.';
			break;
			case UPLOAD_ERR_NO_FILE:
			//Value: 4; No file was uploaded.
				return 'O arquivo não foi enviado.';
			break;
			case UPLOAD_ERR_NO_TMP_DIR://Value: 8; A PHP extension stopped the file upload.
			case UPLOAD_ERR_CANT_WRITE://Value: 6; Missing a temporary folder. Introduced in PHP 5.0.3.
			case UPLOAD_ERR_EXTENSION://Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.
				return 'Não foi possível salvar o arquivo (erro '.$this->info[self::ERROR].').';
			break;
		}
		
		
	}
}


