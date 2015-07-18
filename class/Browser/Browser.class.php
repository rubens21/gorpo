<?php

class Browser 
{
	/**
	 * 
	 * @var BrowserSession
	 */
	private $BrowserSession;
	
	/**
	 * Hhost no qual esse brwoser irá navegar
	 * 
	 * @var string
	 */
	private $host;
	
	/**
	 * Protocolo que será utilizado para compor a URL de navegação
	 * 
	 * @var string
	 */
	private $protocol;
	
	/**
	 * Caminho pesquisado no host
	 * 
	 * @var string
	 */
	private $path;
	
	/**
	 * Lista de cookie que serão enviadas
	 * 
	 * @var array
	 */
	private $cookies = array ();
	
	/**
	 * Lista de parâmretros que serão enviados por POST
	 * 
	 * @var array
	 */
	private $post = array();
	
	/**
	 * Lista de parâmretros que serão enviados por GET
	 * 
	 * @var array
	 */
	private $get = array();
	
	
	private $follow_redirect;
	
	private $follow_occurences = [];
	
	/**
	 * Mensagem de erro em caso de falha
	 * 
	 * @var string
	 */
	private $error;
	
	/**
	 * Método utilizado para requisição (caso tenha parâmetros de POST a reuiqsição será post necessariamente)
	 * @var string
	 */
	private $method = 'GET';
	/**
	 * Define o user agente enviado na resuiqisção
	 * @var string
	 */
	private $user_agent = 'GET';
	
	/**
	 * Cabeçalhos extras que serão enviados
	 * @var array
	 */
	private $headers = [];
	
	private $time_out = 10;
	
	/**
	 * 
	 * @var resource
	 */
	private $CurlResource;
	
	public function __construct($host, $path = '', $protocol = 'http'){
		$this->host = $host;
		$this->protocol = $protocol;
		$this->setPath($path);
	}
	
	public function addCookie($name, $value)
	{
		$this->cookies[$name] = $value;
		return $this;
	} 
	public function addParamPost($name, $value)
	{
		$this->post[$name] = $value;
		return $this;
	} 
	public function addParamGet($name, $value)
	{
		$this->get[$name] = $value;
		return $this;
	} 
	
	public function getError()
	{
		return $this->error;
		return $this;
	}
	
	public function setPath($path)
	{
		$this->path = $path;
		$url= $this->protocol.'://'.$this->host.$this->path;
		if(Util::isValideUrl($url))
		{
			$parts = parse_url($url);
			if($parts['query'])
			{
				$get = [];
				parse_str($parts['query'], $get);
				$this->get = array_merge($this->get, $get);
			}
			$this->path = $parts['path'];
			if(!Util::isValideUrl($this->getUrl()))
				throw new MyException('Parâmetros compoem uma URL mal formatada: '.$this->getUrl());
		}
		else 
			throw new MyException('URL inválida para pesquisa: '.$url);
		return $this;
	}
	
	public function setFollowLocation($bool)
	{
		$this->follow_redirect = (bool)$bool;
		return $this;
	}
	
	public function setMethod($method)
	{
		$this->method = strtoupper($method);
		return $this;
	}
	public function setUserAgent($user_agent)
	{
		$this->user_agent = $user_agent;
		return $this;
	}
	/**
	 * 
	 * @return BrowserSession|NULL
	 */
	public function getSession()
	{
		if($this->BrowserSession)
			return $this->BrowserSession;
		return null;
	}
	
	public function addHeaderExtra($name, $val)
	{
		$this->headers[$name] = $val;
	}
	
	public function send()
	{
		$verbose = fopen('php://temp', 'rw+');
		
		$this->CurlResource = curl_init();
		$this->setRequestExtraHeaders();
		$this->setRequestPost();
		$this->setRequestCookie();
		$this->setRequestReferHeaders();
		$this->setRequestMethod();
		$this->setRequestUserAgent();
		
		curl_setopt($this->CurlResource, CURLOPT_URL, $this->getUrl());
		curl_setopt($this->CurlResource, CURLOPT_HEADER, true);
		curl_setopt($this->CurlResource, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->CurlResource, CURLOPT_ENCODING, '');
		curl_setopt($this->CurlResource, CURLOPT_VERBOSE, true);
 		curl_setopt($this->CurlResource, CURLOPT_STDERR, $verbose);
 		curl_setopt($this->CurlResource, CURLOPT_FOLLOWLOCATION, false);
 		curl_setopt($this->CurlResource, CURLOPT_TIMEOUT, $this->time_out);
		
		$result = curl_exec($this->CurlResource);
		if($result === false){
			$this->error = curl_error($this->CurlResource); 	
			return null;
		}
		$info = curl_getinfo($this->CurlResource);
		
		curl_close($this->CurlResource);
		rewind($verbose);
		$this->setBrowserSession(new BrowserSession($result, $info, $this->cookies, $this->post, stream_get_contents($verbose)));
		
		if($this->follow_redirect && $info['redirect_url'])
			return $this->redirecionarRequisicao($info['redirect_url']);
		else{
			$this->follow_redirect = null;
			return $this->BrowserSession;
		}
	}
	
	private function getUrl()
	{
		return $this->protocol.'://'.$this->host.$this->path.($this->get?'?'.http_build_query($this->get):'');
	}
	
	private function setRequestExtraHeaders()
	{
		foreach ($this->headers as $name => $val)
			curl_setopt($this->CurlResource, CURLOPT_HTTPHEADER, array($name.': '.$val));
	}
	private function setRequestPost()
	{
		if(!$this->post)
			return false;
		curl_setopt($this->CurlResource, CURLOPT_POST, count($this->post));
		curl_setopt($this->CurlResource, CURLOPT_POSTFIELDS, http_build_query($this->post));
		return count($this->post);
	}
	
	private function setRequestCookie()
	{
		if(!$this->cookies)
			return false;
		$cookies = array();
		foreach($this->cookies as $name => $val)
			$cookies[] = $name.'='.$val;
		curl_setopt($this->CurlResource, CURLOPT_COOKIE, implode('; ', $cookies));
		return count($cookies);
	}
	
	private function setRequestReferHeaders()
	{
		if($this->BrowserSession != null)
			curl_setopt($this->CurlResource, CURLOPT_REFERER, $this->BrowserSession->getUrl());
	}
	private function setRequestMethod(){
		if($this->method != 'GET')
			curl_setopt($this->CurlResource, CURLOPT_CUSTOMREQUEST, $this->method);
	}
	private function setRequestUserAgent(){
		if($this->user_agent)
			curl_setopt($this->CurlResource, CURLOPT_USERAGENT, $this->user_agent);
	}
	
	private function setBrowserSession(BrowserSession $Page)
	{
		$this->post = array();
		$this->get = array();
		$this->headers = array();
		$this->cookies = $Page->getAllCookies();
		$this->BrowserSession = $Page;
	}
	
	private function redirecionarRequisicao($redirect_url)
	{
		$this->follow_occurences[] = $redirect_url;
		$this->setPath(str_replace(array($this->protocol.'://'.$this->host), '', $redirect_url));
		return $this->send();
	}
	
}

