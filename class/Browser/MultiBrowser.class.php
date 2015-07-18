<?php

/**
 *
 * Objeto que expande o Browser criando uma estrutura de multi navegadores para facilitar o multi request
 * @author rubens21
 */
class MultiBrowser extends Browser {

	/**
	 * Lista de navegadores que serão executados simultaneamente.
	 *
	 * @var Browser[]
	 */
	private $Browsers = [ ];

	/**
	 */
	public function __construct()
	{
	}

	/**
	 * Adiciona um navegador na lista para ser executado em paralelo.
	 *
	 * @param string $label Nome que irá identificar o navegador criado
	 * @return Browser O novo navegador que acabou de ser criado
	 */
	public function createBrowser($label, $host, $path, $protocol = 'http')
	{
		$this->Browsers [$label] = new Browser( $host, $path, $protocol );
		return $this->Browsers [$label];
	}

	/**
	 *
	 * @param string $label
	 * @return Browser
	 */
	public function browser($label)
	{
		return $this->Browsers [$label];
	}

	public function countRequests()
	{
		return count($this->Browsers);
	}
	
	/**
	 * Executa todos os navegadores fazendo as requisições
	 * @return void 
	 */
	public function execute()
	{
		if($this->countRequests() == 0)
			return false;
		// create the multiple cURL handle
		$mh = curl_multi_init();
		
		// add the two handles
		foreach ( $this->Browsers as $Browser )
		{
			$Browser->prepare();
			curl_multi_add_handle( $mh, $Browser->getCurlResource() );
		}
		
		$active = null;
		// execute the handles
		// execute the handles
		do {
			$n=curl_multi_exec($mh,$active);
			usleep(800);
		} while ($active);
		
		// close the handles
		foreach ( $this->Browsers as $label => $Browser )
		{
			curl_multi_remove_handle( $mh, $Browser->getCurlResource() );
			$this->Browsers [$label]->processResult( curl_multi_getcontent( $Browser->getCurlResource() ) );
		}
		curl_multi_close( $mh );
	}
}

