<?php

/**
 * Esse objeto foi projetado para fazer da maneira mais simples possível a abstração de um navegador, perpetuando os
 * cookies por mais de uma requisição para poder facilitar a realização de requisiçoes sequênciais.
 * Note que o método send, que faz a requisição, pode falhar por motivos internos do CURL e retornar NULL. Portanto,
 * a classe fornecesse uma lista de erros insternos que podem ser obtidos a qualquer momento pelo método getErros
 * 
 * @author rubens21
 */
class Browser {

	/**
	 * Objeto que possui o documento carregado na última requisição
	 * 
	 * @var BrowserDoc
	 */
	private $BrowserDoc;

	/**
	 * Host no qual esse brwoser irá navegar
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
	private $cookies = [];

	/**
	 * Lista de parâmretros que serão enviados por POST
	 * 
	 * @var array
	 */
	private $post = [];

	/**
	 * Lista de parâmretros que serão enviados por GET
	 * 
	 * @var array
	 */
	private $get = [];
	
	private $curl_opts = [];

	/**
	 * Informa se a requisição deve seguir automaticamente a ordem de redirecionamento.
	 * 
	 * @todo Atualmente esse redirecionamento NÃO é feito diretamente através do atributo do CURL pois os cookies não
	 *       são enaminhados, provavelmente há uma solução para isso.
	 * @var bool
	 */
	private $follow_redirect = false;

	/**
	 * Lista das URLs pelas quais a requisição passou caso tenha sido permitido o redirecionamento.
	 * 
	 * @var array
	 */
	private $follow_occurences = [ ];

	/**
	 * Mensagem de erro em caso de falha
	 * 
	 * @var string
	 */
	private $error;

	/**
	 * Método utilizado para requisição (caso tenha parâmetros de POST a reuiqsição será post necessariamente)
	 * 
	 * @var string
	 */
	private $method = 'GET';

	/**
	 * Define o user agente enviado na resuiqisção
	 * 
	 * @var string
	 */
	private $user_agent = 'GET';

	/**
	 * Cabeçalhos extras que serão enviados
	 * 
	 * @var array
	 */
	private $headers = [ ];

	/**
	 * Tempo limite de espera pela resposta do servidor em segundos.
	 * 
	 * @todo Fazer método para setar e getar
	 * @var string
	 */
	private $time_out = 40;

	/**
	 * Resource do CURL utilizado pelo objeto para fazer as requisições.
	 * É aberto e fechado a cada requisição.
	 * 
	 * @var resource
	 */
	private $CurlResource;
	
	/**
	 * Resource de arquivo na memória utilizado para capturar o verbose do CURL
	 * 
	 * @var resource 
	 */
	private $verbose;
	

	/**
	 * Cria o navegador
	 * 
	 * @param string $host Host onde o navegador estará conectado. Não é possível alterar esse valor posteriormente.
	 * @param string $path Caminho para o qual a requisição inicialmente será feita, isso pode ser alterado
	 *        posteriormente.
	 * @param string $protocol Protocolo de acesso utilizado (não pode ser alterado posteriormente)
	 */
	public function __construct($host, $path = '', $protocol = 'http')
	{
		$this->host = $host;
		$this->protocol = $protocol;
		$this->setPath( $path );
		
		$list[] = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201';
		$list[] = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; it; rv:2.0b4) Gecko/20100818re';
		$list[] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9a3pre) Gecko/20070330';
		$list[] = 'Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.9.2a1pre) Gecko';
		$list[] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36';
		$list[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.1 Safari/537.36';
		$list[] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36';
		$list[] = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36';
		$list[] = 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2226.0 Safari/537.36';
		$list[] = 'Mozilla/5.0 (Windows NT 6.4; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2225.0 Safari/537.36';
		$list[] = 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2225.0 Safari/537.36';
		$list[] = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2224.3 Safari/537.36';
		$list[] = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36';
		$list[] = 'Mozilla/5.0 (Windows NT 4.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36';
		
		$this->user_agent = $list[rand(0,13)];
		
		
	}

	/**
	 * Inclui um valor para ser setado como cookie durante as requisições
	 * 
	 * @param string $name Nome do cookie
	 * @param string $value Valor atribuído ao cookie
	 * @return Browser A instância do próprio objeto.
	 */
	public function addCookie($name, $value)
	{
		$this->cookies [$name] = $value;
		return $this;
	}

	/**
	 * Inclui um valor para ser enviado via POST
	 * 
	 * @param string $name Nome do parâmetro
	 * @param string $value Valor atribuído ao parâmetro
	 * @return Browser A instância do próprio objeto.
	 */
	public function addParamPost($name, $value)
	{
		$this->post [$name] = $value;
		return $this;
	}

	/**
	 * Inclui um valor para ser enviado via GET
	 * 
	 * @param string $name Nome do parâmetro
	 * @param string $value Valor atribuído ao parâmetro
	 * @return Browser A instância do próprio objeto.
	 */
	public function addParamGet($name, $value)
	{
		$this->get [$name] = $value;
		return $this;
	}

	/**
	 * Retorna a mensagem de erro caso a requisição não tenha ocorrido devido a algum erro.
	 * 
	 * @return string Mensagem de erro capturada pela classe em caso de falha
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Altera o caminho para onde será feita a requisição no servidor.
	 * 
	 * @param string $path Caminho no servidor onde será feita a requisição (/endereco/qualquer/exemplo)
	 * @return Browser A instância do próprio objeto.
	 */
	public function setPath($path)
	{
		$this->path = $path;
		$url = $this->protocol . '://' . $this->host . $this->path;
		if (Check::isUrl( $url ))
		{
			$parts = parse_url( $url );
			if ($parts ['query'])
			{
				$get = [ ];
				parse_str( $parts ['query'], $get );
				$this->get = array_merge( $this->get, $get );
			}
			$this->path = $parts ['path'];
			if (! Check::isUrl( $this->getUrl() ))
				throw new MyException( 'Parâmetros compoem uma URL mal formatada: ' . $this->getUrl() );
		}else
			throw new MyException( 'URL inválida para pesquisa: ' . $url );
		return $this;
	}

	/**
	 * Altera o comportamento do navegador para o caso de receber cabeçalhos indicando redirecionamento.
	 * 
	 * @param bool $bool True para permitir redireciinamento
	 * @return Browser A instância do próprio objeto.
	 */
	public function setFollowLocation($bool)
	{
		$this->follow_redirect = ( bool ) $bool;
		return $this;
	}

	/**
	 * Altera o método de requisiçã HTTP.
	 * Note que o método não faz restrições, portanto defina com cautela.
	 * 
	 * @param string $method Nome do novo método de requisição. Por padrão é GET ou POST (caso tenha parâmetros post)
	 * @return Browser A instância do próprio objeto.
	 */
	public function setMethod($method)
	{
		$this->method = strtoupper( $method );
		return $this;
	}

	/**
	 * Altera o parâmetro que indica para o servidor qual o ciente está sendo utilizado,
	 * 
	 * @todo Desenvolver método que retorne a string que identifica navegadores comuns ou até mesmo que sorteie um
	 *       qualquer assim que o objeto é criado.
	 * @param string $user_agent Nome que irá identificar o cliente utilizado
	 * @return Browser A instância do próprio objeto.
	 */
	public function setUserAgent($user_agent)
	{
		$this->user_agent = $user_agent;
		return $this;
	}

	/**
	 * Obtém o objeto que possui o documento carregado no último request (será null caso não tenha sido feito nenhum
	 * request)
	 * 
	 * @return BrowserDoc|NULL Documento carregado no último request (ou null caso não tenha feito nenhum)
	 */
	public function getLastDoc()
	{
		if ($this->BrowserDoc)
			return $this->BrowserDoc;
		return null;
	}

	/**
	 * Adiciona livremente qualquer cabeçalho para envio na requisição.
	 * Note que o método não faz qualquer validação nem mesmo com os cabeçalhos utilizados pela classe, porém os
	 * cabeçalhos extras são setados antes dos demais cabecalhos, portanto eles poderão ser sobrescritos pelos
	 * cabeçalhos originais utilizados pelo objeto.
	 * 
	 * @param string $name Nome do cabeçalho
	 * @param string $val Valor que será atribúido ao cabeçalho.
	 */
	public function addHeaderExtra($name, $val)
	{
		$this->headers [$name] = $val;
	}

	/**
	 * Envia a requisição para o servidor.
	 * Caso haja alguma falha na requisição CURL o método poderá retornar NULL.
	 * Note que retornar um objeto BrowserDoc não indica que a requisição teve sucesso, pois o resultado da
	 * requisição poderá ter código HTTP diferente de 200.
	 * É possível obter a mensagem de erro do CURL (caso tenha havido) através do método getErro
	 * 
	 * @return BrowserDoc|NULL Documento obtido com a requisição ou NULL caso tenha havido um erro no CURL.
	 */
	public function send()
	{
		$this->prepare();
		return $this->processResult(curl_exec( $this->CurlResource ));
	}
	
	public function setCurlOpt($opt, $value)
	{
		$this->curl_opts[$opt] = $value;
	}
	
	protected function processResult($result)
	{
		if ($result === false)
		{
			$this->error = curl_error( $this->CurlResource );
			return null;
		}
		$info = curl_getinfo( $this->CurlResource );
		
		curl_close( $this->CurlResource );
		rewind( $this->verbose );
		$this->setBrowserDoc( new BrowserDoc( $result, $info, $this->cookies, $this->post, stream_get_contents( $this->verbose ) ) );
		
		$this->follow_redirect = false;
		return $this->BrowserDoc;
	}
	
	protected function prepare()
	{
		$this->verbose = fopen( 'php://temp/browsertmp-'.time().rand(0, 99999), 'rw+' );
		
		$this->CurlResource = curl_init();
		$this->setRequestExtraHeaders();
		$this->setRequestPost();
		$this->setRequestCookie();
		$this->setRequestReferHeaders();
		$this->setRequestMethod();
		$this->setRequestUserAgent();
		$this->setRequestCustomCurlOpts();
		
		curl_setopt( $this->CurlResource, CURLOPT_URL, $this->getUrl() );
		curl_setopt( $this->CurlResource, CURLOPT_HEADER, true );
		curl_setopt( $this->CurlResource, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $this->CurlResource, CURLOPT_ENCODING, '' );
		curl_setopt( $this->CurlResource, CURLOPT_VERBOSE, true );
		curl_setopt( $this->CurlResource, CURLOPT_STDERR, $this->verbose );
		if($this->follow_redirect){
			curl_setopt($this->CurlResource, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt($this->CurlResource, CURLOPT_COOKIEFILE, "");
		}
		curl_setopt( $this->CurlResource, CURLOPT_TIMEOUT, $this->time_out );
	}
	
	/**
	 * Obtém o resource CURL criado para fazer a requisição
	 * 
	 * @return resource
	 */
	public function getCurlResource()
	{
		return $this->CurlResource;
	}

	/**
	 * Compõe a URL para ser requisitada.
	 * 
	 * @return string URL montada para a requisição incluindo o protocolo, host, caminho e querystring
	 */
	private function getUrl()
	{
		return $this->protocol . '://' . $this->host . $this->path . ($this->get ? '?' . http_build_query( $this->get ) : '');
	}

	/**
	 * Seta os cabeçalhos extras no objeto CURL aberto para a requisição.
	 * 
	 * @return void
	 */
	private function setRequestExtraHeaders()
	{
		foreach ( $this->headers as $name => $val )
			curl_setopt( $this->CurlResource, CURLOPT_HTTPHEADER, array ($name . ': ' . $val ) );
	}

	/**
	 * Set os cabeçalhos destinados ao envio de parâmetros POST no objeto CURL aberto para a requisição.
	 * 
	 * @return bool|int Quantidade de parâmetros setados ou false caso não haja parâmetros.
	 */
	private function setRequestPost()
	{
		if (! $this->post)
			return false;
		curl_setopt( $this->CurlResource, CURLOPT_POST, count( $this->post ) );
		curl_setopt( $this->CurlResource, CURLOPT_POSTFIELDS, http_build_query( $this->post ) );
		return count( $this->post );
	}
	private function setRequestCustomCurlOpts()
	{
		if (! $this->curl_opts)
			return false;
		foreach ($this->curl_opts as $opt => $val)
			curl_setopt( $this->CurlResource, $opt, $val );
		return count( $this->curl_opts );
	}

	/**
	 * Set os cabeçalhos destinados ao envio de parâmetros COOKIE no objeto CURL aberto para a requisição.
	 * 
	 * @return bool|int Quantidade de parâmetros setados ou false caso não haja parâmetros.
	 */
	private function setRequestCookie()
	{
		if (! $this->cookies)
			return false;
		$cookies = array ();
		foreach ( $this->cookies as $name => $val )
			$cookies [] = $name . '=' . $val;
		curl_setopt( $this->CurlResource, CURLOPT_COOKIE, implode( '; ', $cookies ) );
		return count( $cookies );
	}

	/**
	 * Seta o cabeçalho que identifica a http_refer no objeto CURL aberto para a requisição.
	 * 
	 * @return void
	 */
	private function setRequestReferHeaders()
	{
		if ($this->BrowserDoc != null)
			curl_setopt( $this->CurlResource, CURLOPT_REFERER, $this->BrowserDoc->getUrl() );
		else
			curl_setopt( $this->CurlResource, CURLOPT_REFERER, $this->getUrl() );
	}

	/**
	 * Seta o cabeçalho que identifica o método de requisição no objeto CURL aberto para a requisição.
	 * 
	 * @return void
	 */
	private function setRequestMethod()
	{
		if ($this->method != 'GET')
			curl_setopt( $this->CurlResource, CURLOPT_CUSTOMREQUEST, $this->method );
	}

	/**
	 * Seta o cabeçalho que identifica o cliente utilizado na requisição
	 * 
	 * @return void
	 */
	private function setRequestUserAgent()
	{
		if ($this->user_agent)
			curl_setopt( $this->CurlResource, CURLOPT_USERAGENT, $this->user_agent );
	}

	/**
	 * Armazena o documento obtido na última requisição e reseta valores atribuídos para a última requisiçã.
	 * 
	 * @return void
	 */
	private function setBrowserDoc(BrowserDoc $Page)
	{
		$this->post = array ();
		$this->get = array ();
		$this->headers = array ();
		$this->cookies = $Page->getAllCookies();
		$this->BrowserDoc = $Page;
	}
}

