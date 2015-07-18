<?php

/**
 * Objeto que representa o documento retornado por uma requisição HTTP feita pela classe Browser
 * 
 * @author rubens21
 */
class BrowserDoc {

	/**
	 * Informação obtida pela função curl_getinfo sobre a requisição que originou esse documento.
	 * 
	 * @see http://php.net/manual/en/function.curl-getinfo.php
	 * @var mixed
	 */
	private $info;

	/**
	 * Conteúdo obtido com na requisição que originou esse documento.
	 * 
	 * @var string
	 */
	private $body;

	/**
	 * Cabeçalhos HTTP vindos na requisição que originou esse documento.
	 * 
	 * @var array
	 */
	private $headers = [ ];

	/**
	 * Cabeçalhos ENVIADOS na requisição que originou esse documento.
	 * 
	 * @var array
	 */
	private $req_headers = [ ];

	/**
	 * Cookies que foram setados na requisição que originou esse documento.
	 * 
	 * @var array
	 */
	private $new_cookies = [ ];

	/**
	 * Cookies que já existiam antes da última requisicão (podem conter valores que tb vieram na requisicão)
	 * 
	 * @var array
	 */
	private $old_cookies = [ ];

	/**
	 * Parâmetros que foram enviados por post na requisição que originou esse documento.
	 * 
	 * @var array
	 */
	private $last_post = [ ];

	/**
	 * Cria o objeto baseado em dados fornecidos pela classe Browsers
	 * 
	 * @param string $http_response Conteúdo original obtido na requisição, que inclui os cabeçalhos HTTP
	 * @param mixed $response_info Informações obtidas com a função curl_getinfo do CURL
	 * @param array $origin_cookies Lista dos cookies que foram enviados na requisição
	 * @param array $origin_post Lista os parâmetros enviados por post na requisição
	 * @param string $request_headers Cabeçalhos enviados na requisição
	 */
	public function __construct($http_response, $response_info, array $origin_cookies, array $origin_post, $request_headers)
	{
		$this->info = $response_info;
		$this->body = substr( $http_response, $this->info ['header_size'] );
		$this->old_cookies = $origin_cookies;
		$this->last_post = $origin_post;
		$this->parseHeaders( $http_response );
		$this->parseReqHeaders( $request_headers );
	}

	/**
	 * Obtém o conteúdo do documento obtido.
	 * 
	 * @return string conteúdo do documento obtido.
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * Obtém a URL que foirequisitada
	 * 
	 * @return mixed URL que foirequisitada
	 */
	public function getUrl()
	{
		return $this->info ['url'];
	}

	/**
	 * Obtém o código HTTP respondido pelo servidor nessa requisição.
	 * 
	 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10
	 * @return number Código HTTP respondido pelo servidor nessa requisição.
	 */
	public function getHttpCode()
	{
		return $this->info ['http_code'];
	}

	/**
	 * Obtém o tipo do conteúdo obtido;
	 * 
	 * @return string tipo do conteúdo obtido;
	 */
	public function getContentType()
	{
		return $this->info ['content_type'];
	}

	/**
	 * Obtém os cabeçalhos obtidos na requisição
	 * 
	 * @return array cabeçalhos obtidos na requisição
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * Obtém os cabeçalhos ENVIADOS na última requisição
	 * 
	 * @return array cabeçalhos ENVIADOS na última requisição
	 */
	public function getReqHeaders()
	{
		return $this->req_headers;
	}

	/**
	 * Obtém a lista de cookies que foram enviados nos cabeçalhos de reuqisição (com os valore soriginais, que podem ter
	 * sido alterados)
	 * 
	 * @return array Valore de cookies enviados na requisição (os valores podem ter sido alterados pelos cabeçalhos de
	 *         resposta)
	 */
	public function getOriginCookies()
	{
		return $this->old_cookies;
	}

	/**
	 * Obtém a lista dos cookies setados ou alterados pelos cabeçalhos de RESPOSTA da requisição
	 * 
	 * @return array Lista dos cookies setados ou alterados pelos cabeçalhos de RESPOSTA da requisição
	 */
	public function getNewCookies()
	{
		return $this->new_cookies;
	}

	/**
	 * Obtém a lista de todos os cookies atuais da sessão, ou seja, os velhos e os novos
	 * 
	 * @return array lista de todos os cookies atuais da sessão, ou seja, os velhos e os novos
	 */
	public function getAllCookies()
	{
		return array_merge( $this->old_cookies, $this->new_cookies );
	}

	/**
	 * Método que extrai dos cabeçalhos HTTP recebidos as informações de resposta e identifica os COOKIES setados
	 * 
	 * @param string $http_response Cabeçalhos HTTP recebidos na requisição
	 * @return void
	 */
	private function parseHeaders($http_response)
	{
		$header = substr( $http_response, 0, $this->info ['header_size'] );
		
		// Limpar e juntar info da requisicao com headers
		foreach ( explode( "\r\n", $header ) as $j => $line )
		{
			// Quando for string informando o HTTP status, nao considerar (pular)
			if ($line != '' && substr( $line, 0, 5 ) != 'HTTP/')
			{
				// Quebrar o header em um array KEY=>VALUE
				list ( $key, $value ) = explode( ': ', $line );
				
				// Se a key for de cookie, considerar os valores até o primeiro ";"
				if ($key == 'Set-Cookie')
				{
					preg_match( "/(?<name>[\w\d-.]+)=(?<val>[^;]+)/", $value, $cookie );
					parse_str( $cookie [1], $arr );
					$this->new_cookies [$cookie ['name']] = $cookie ['val'];
				}
				
				// Caso exista a mesma KEY mais de 1 vez (Ex: Set-Cookie), concatenar
				if ($this->headers [$key])
				{
					if (! is_array( $this->headers [$key] ))
					{
						$aux = $this->headers [$key];
						$this->headers [$key] = [ $aux ];
					}
					$this->headers [$key] [] = $value;
				}else
					$this->headers [$key] = $value;
			}
		}
	}

	/**
	 * Método que extrai dados dos cabeçalhos HTTP ENVIADOS
	 * 
	 * @param string $http_response Cabeçalhos HTTP enviados na requisição
	 * @return void
	 */
	private function parseReqHeaders($request_headers)
	{
		$linhas = explode( "\n", $request_headers );
		$headers = [ ];
		foreach ( explode( "\n", $request_headers ) as $info )
		{
			$info = trim( $info );
			if ($info [0] != '*')
			{
				if (! $info)
					break;
				$headers [] = $info;
			}
		}
		$this->req_headers = $headers;
	}
}

