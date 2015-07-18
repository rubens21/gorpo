<?php

class BrowserSession
{
	private $info;
	private $body;
	private $headers;
	private $req_headers;
	private $new_cookies = [];
	private $old_cookies = [];
	private $old_post = [];
	
	
	public function __construct($http_response, $response_info, array $origin_cookies, array $origin_post, $request_headers)
	{
		$this->info = $response_info;
		$this->body = substr($http_response, $this->info['header_size']);
		$this->old_cookies = $origin_cookies;
		$this->old_post = $origin_post;
		$this->parseHeaders($http_response);
		$this->parseReqHeaders($request_headers);

	}

	public function getBody(){
		return $this->body;
	}
	public function getUrl(){
		return $this->info['url'];
	}
	public function getHttpCode(){
		return $this->info['http_code'];
	}
	
	public function getContentType(){
		return $this->info['content_type'];
	}
	public function getHeaders(){
		return $this->headers;
	}
	public function getReqHeaders(){
		return $this->req_headers;
	}
	public function getOriginCookies(){
		return $this->old_cookies;
	}
	
	public function getNewCookies(){
		return $this->new_cookies;
	}
	
	public function getAllCookies(){
		return array_merge( $this->old_cookies, $this->new_cookies);
	}
	
	private function parseHeaders($http_response)
	{
		$header = substr($http_response, 0, $this->info['header_size']);
	
		// Limpar e juntar info da requisicao com headers
		foreach (explode("\r\n", $header) as $j => $line)
		{
			// Quando for string informando o HTTP status, nao considerar (pular)
			if ($line != '' && substr($line, 0, 5) != 'HTTP/')
			{
				// Quebrar o header em um array KEY=>VALUE
				list($key, $value) = explode(': ', $line);
	
				//Se a key for de cookie, considerar os valores até o primeiro ";"
				if ($key == 'Set-Cookie') {
					preg_match("/(?<name>[\w\d-.]+)=(?<val>[^;]+)/", $value, $cookie);
					parse_str($cookie[1], $arr);
					$this->new_cookies[$cookie['name']] = $cookie['val'];
				}
				
				// Caso exista a mesma KEY mais de 1 vez (Ex: Set-Cookie), concatenar
				if ($this->headers[$key])
				{
					if(!is_array($this->headers[$key]))
					{
						$aux = $this->headers[$key];
						$this->headers[$key] = [$aux];
					}
					$this->headers[$key][] = $value;
				}else
					$this->headers[$key] = $value;
				}
		}
	}
	
	private function parseReqHeaders($request_headers)
	{
		$linhas = explode("\n", $request_headers);
		$headers = [];
		foreach(explode("\n", $request_headers) as $info)
		{
			$info = trim($info);
			if($info[0] != '*')
			{
				if(!$info)
					break;
				$headers[] = $info;
			}
			
		}
		$this->req_headers = $headers;
	}
	
}

