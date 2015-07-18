<?php

/**
 * Trata de forma mais completa o erro.
 * Com ela é possível fazer uma auditoria global do estado do sistema no momento de uma falha.
 * Ao disparar uma exceção por essa classe um e-mail é enviado para o administrador, cujo e-mail é determinado no
 * arquivo de configuração,
 * com um relatório com todos os dados possíveis de registro no momento da falha.
 * 
 * @author Rubens de Souza Silva <rubens21@gmail.com>
 * @package Utils
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class MyException extends Exception {

	/**
	 * Corpo de erros no sistemas
	 * 
	 * @var array
	 */
	private $erros = [ ];

	/**
	 * Nome que será usado para salvar o arquivo no PATH de erros.
	 * 
	 * @var string
	 */
	private $erro_nome;

	/**
	 * Contador de erros em uma mesma execução.
	 * Por isso é estático, pois não depende da instancia.
	 * 
	 * @var int
	 */
	private static $cont_erros;

	/**
	 * Id único gerado para cada erro baseado no arquivo de disparo e a linha de execução.
	 * Esse código pode ser utilizado
	 * para facilitar o catálogo de erros.
	 * 
	 * @var string
	 */
	private $id_unico;

	/**
	 * Identificador único e randômico que permite identificar a execução para agrugar erros de uma mesma execução.
	 * 
	 * @var int.
	 */
	private static $identificador_execucao;

	/**
	 * Permite armazenar a informação se o erro 'pai' (o primeiro da execução) foi enviado por e-mail, para nesse caso
	 * não enviar mais mensagens de erros e evitar a superlotação de mensagens de alerta.
	 * 
	 * @var bool.
	 */
	private static $erro_pai_enviado = false;

	/**
	 * Pode ser chamado diretamente por um throw ou quando o PHP dispara um erro (WARNING, PARSE ou FATAL).
	 * No segundo caso um próprio método erro() dessa clase dispara a exceção.
	 * 
	 * @param String $message Texto descritivo sobre o que pode ter acontecido na rotina que disparou o erro.
	 * @param int $gravidade Valor inteiro para indicar a gravidade (0 a 3) da exceção. No caso de ser disparada por um
	 *        	erro do PHP esse campo é um valor constante que indica qual tipo de erro.
	 * @param array $contexto Array com valores do debug_backtrace() (nativo do PHP), pode não ser enviado ou ignorado
	 *        	com valor false. No caso de erros no PHP tráz informações sobre linha, arquivo e contexto (veja doc
	 *        	set_error_handler)
	 * @param bool $erro_php É usado apenas pelo método erro() da classe para indicar que não é uma exceção do sistema,
	 *        	mas sim um erro no PHP. O método erro() está sendo usado como handle dos erros no PHP para que os
	 *        	usuários não vejam mensagens de erros.
	 * @return void
	 */
	public function __construct($message, $gravidade = __CLASS__, $contexto = false, $erro_php = false)
	{
		if (! self::$identificador_execucao) self::$identificador_execucao = str_pad ( rand ( 1, 9999 ), 4, '0', STR_PAD_LEFT );
		$this->message = $message;
		$this->erro_nome = self::$identificador_execucao . '-' . ++ self::$cont_erros . '-' . $gravidade . "-" . date ( 'Y-m-d-H-i-s' ) . ".json";
		
		$this->erros ['level'] = $gravidade;
		$this->erros ['datetime'] = date ( "H:i:s Y-m-d", time () );
		$this->erros ['mensagem'] = $message;
		
		$this->erros ['db_error'] = DB::error ();
		
		if ($erro_php)
		{
			$erro_linha = $contexto ['linha'];
			$erro_arquivo = $contexto ['arquivo'];
			$message = strip_tags ( $message );
			
			/*
			 * Não sei porque diabos esta chave as vezes vem com valores recursivos infinitos que causam erro no
			 * sistema.
			 */
			$contexto ['contexto'] ["GLOBALS"] = " ";
			// muitas vezes o erro do php não traz o debug_backtrace
			$contexto ['Backtrace'] = debug_backtrace ();
		} else
		{
			$erro_linha = self::getLine ();
			$erro_arquivo = self::getFile ();
			if (SYS_MODO_DEVEL) echo "<pre><br /><b>$gravidade</b> $message\n\nno arquivo $erro_arquivo Linha: $erro_linha<br /><br /></pre>";
		}
		
		$this->id_unico = basename ( $erro_arquivo, '.php' ) . '.' . $erro_linha;
		
		$this->erros ['PHP'] ['Arquivo'] = $erro_arquivo;
		$this->erros ['PHP'] ['Linha'] = $erro_linha;
		$this->erros ['PHP'] ['Codigo'] = $this->id_unico;
		
		/*
		 * Caso tenha sido passado um array com o contexto do erro/exceção.
		 */
		if ($contexto) $this->erros ['Contexto'] = $contexto;
		else
			$this->erros ['Backtrace'] = debug_backtrace ();
		
		$this->erros ["GET"] = $_GET;
		$this->erros ["POST"] = $_POST;
		$this->erros ["REQUEST"] = $_REQUEST;
		if (isset ( $_SESSION )) $this->erros ["Sessao"] = $_SESSION;
		
		$this->erros ["Cookies"] = $_COOKIE;
		$this->erros ["Arquivos"] = $_FILES;
		$this->erros ["Servidor"] = $_SERVER;
		$this->erros ["APPS"] = ( array ) System::getInstance ();
		foreach ( SysDebug::getErros () as $err )
			$this->erros ['user_erros'] [] = [ 'Label' => $err ['Label'],'VAR' => $err ['VAR'],'file' => $err ['file'],'line' => $err ['line'],'stringy' => $err ['stringy'] ];
		
		$json = utf8_decode ( json_encode ( $this->erros, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		
		$tam_file = file_put_contents ( PATH_LOG_ERRO . $this->erro_nome, $json );
		if (SYS_MODO_DEVEL)
		{
			echo "<br/>Erro: " . $this->erro_nome;
			echo "<br/><pre>$json</pre>";
		}
		
		/*
		 * Verifica se o sistema está configurado para enviar um e-mail para o administrador com o json.
		 */
		if (ERROS_ENVIAR_EMAIL && ! self::$erro_pai_enviado)
		{
			$largura_coluna = 20;
			$msg = '<code><pre>' . SYS_NAME . " Disse:\n" . $message;
			
			$msg .= "\n\n" . str_pad ( 'Arquivo: ', $largura_coluna ) . $erro_arquivo;
			$msg .= "\n" . str_pad ( 'Codigo: ', $largura_coluna ) . $this->id_unico;
			$msg .= "\n" . str_pad ( 'Linha: ', $largura_coluna ) . $erro_linha;
			$msg .= "\n" . str_pad ( 'Erro banco: ', $largura_coluna ) . DB::error ();
			
			$msg .= "\n----------------------------------------------------------------------------------\n";
			$msg .= "\n" . str_pad ( "Horario:", $largura_coluna ) . date ( 'H:i:s d/m/Y' );
			if (array_key_exists ( 'HTTP_X_FORWARDED_FOR', $_SERVER ))
			{
				$msg .= "\n" . str_pad ( "IP:", $largura_coluna ) . $_SERVER ['HTTP_X_FORWARDED_FOR'];
				$msg .= "\n" . str_pad ( "Proxy:", $largura_coluna ) . '<a href="http://www.geoiptool.com/?IP=' . $_SERVER ['REMOTE_ADDR'] . '" target="_blank">' . $_SERVER ['REMOTE_ADDR'] . '</a>';
			} else
				$msg .= "\n" . str_pad ( "IP:", $largura_coluna ) . '<a href="http://www.geoiptool.com/?IP=' . $_SERVER ['REMOTE_ADDR'] . '" target="_blank">' . $_SERVER ['REMOTE_ADDR'] . '</a>';
			$msg .= "\n" . str_pad ( "Navegador:", $largura_coluna ) . $_SERVER ['HTTP_USER_AGENT'];
			$msg .= "\n" . str_pad ( "URL requisitada:", $largura_coluna ) . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
			$msg .= "\n" . str_pad ( "POST:", $largura_coluna ) . count ( $_POST );
			$msg .= "\n" . str_pad ( "GET:", $largura_coluna ) . count ( $_GET );
			$msg .= "\n" . str_pad ( "FILES:", $largura_coluna ) . count ( $_FILES );
			$msg .= "\n" . str_pad ( "URL raiz:", $largura_coluna ) . URL_ROOT;
			$msg .= "\n\n\n" . str_pad ( "JSON", $largura_coluna ) . $json;
			
			if ($tam_file)
			{
				$msg .= "\n----------------------------------------------------------------------------------\n";
				$msg .= "\n" . str_pad ( "Arquivo em:", $largura_coluna ) . PATH_LOG_ERRO . $this->erro_nome;
			} else
				$msg .= "\n\nNÃO FOI POSSÍVEL ESCREVER O ARQUIVO EM DISCO!";
			$msg .= "</pre></code>";
			if (ERROS_ANEXAR)
			{
				$anexo_codificado = chunk_split ( base64_encode ( $json ) );
				$mailheaders = "\nMIME-version: 1.0\n";
				$mailheaders .= "Content-type: multipart/mixed; ";
				$mailheaders .= "boundary=\"Message-Boundary\"\n";
				$mailheaders .= "Content-transfer-encoding: 7BIT\n";
				$mailheaders .= "X-attachments: $this->erro_nome";
				$body_top = "--Message-Boundary\n";
				$body_top .= "Content-type: text/html; charset=utf8\n";
				$body_top .= "Content-transfer-encoding: 7BIT\n";
				$body_top .= "Content-description: Mail message body\n\n";
				$msg = $body_top . $msg;
				$msg .= "\n\n--Message-Boundary\n";
				$msg .= "Content-type: application/json; name=\"$this->erro_nome\"\n";
				$msg .= "Content-Transfer-Encoding: BASE64\n";
				$msg .= "Content-disposition: attachment; filename=\"$this->erro_nome\"\n\n";
				$msg .= "$anexo_codificado\n";
				$msg .= "--Message-Boundary--\n";
			}
			$mailheaders .= "\nReferences: <" . $this->id_unico . "@" . parse_url ( URL_ROOT, PHP_URL_HOST ) . ">\n";
			$msg .= "\n\nAtt, " . SYS_NAME . " (" . SIS_EMAIL . ")";
			$assunto = "[" . $gravidade . "][" . str_replace ( "http://", '', URL_ROOT ) . '] ' . utf8_decode ( substr ( $this->id_unico, 0, 30 ) );
			if (mail ( ERROS_EMAIL, $assunto, $msg, "From: " . SYS_NAME . " <" . SIS_EMAIL . ">" . $mailheaders )) self::$erro_pai_enviado = true;
			else
				file_put_contents ( PATH_LOG_ERRO . "EMAIL_$this->erro_nome", "Erro ao enviar e-mail do erro $this->erro_nome" );
		}
	}

	/**
	 * Este método é um Handle da função nativa do PHP para capturar os erros no código (veja documentação sobre
	 * set_error_handler()).
	 * Os erros não são exibidos para os usuários. Ao invés disso é apresentada uma mensagem amigável enquanto é
	 * disparada uma exceção
	 * para registrar o log de erro com os dados do ambiente no momento do erro. Todos seus parâmetros são enviados
	 * automativamente pelo PHP,
	 * não é necessário tratá-los.
	 * 
	 * @param Integer $gravidade Valor inteiro da constante que indica o tipo do erro (E_ERROR, E_WARNING, etc...)
	 * @param String $message Mensagem enviada pelo PHP explicando o erro.
	 * @param String $arquivo Endereço do arquivo onde aconteceu o erro.
	 * @param Integer $linha Número da linha no arquivo onde aconteceu o errro.
	 * @param Array $contexto Array com diversos dados sobre o ambiente no momento do errro. (muito semelhante ao
	 *        	debug_backtrace()).
	 * @return void
	 */
	public static function erro($gravidade, $message, $arquivo, $linha, $contexto)
	{
		$erros = array (E_ERROR => "Error",E_WARNING => "Warning",E_PARSE => "Parsing_Error",E_NOTICE => "Notice",E_CORE_ERROR => "Core_Error",E_CORE_WARNING => "Core_Warning",E_COMPILE_ERROR => "Compile_Error",E_COMPILE_WARNING => "Compile_Warning",E_USER_ERROR => "User_Error",
		E_USER_WARNING => "User_Warning",E_USER_NOTICE => "User_Notice",E_STRICT => "Runtime_Notice",E_RECOVERABLE_ERROR => 'Catchable_Fatal_Error' );
		$array_ignorar = array (E_CORE_ERROR => "Core_Error",E_CORE_WARNING => "Core_Warning",E_COMPILE_ERROR => "Compile_Error",E_COMPILE_WARNING => "Compile_Warning",E_USER_ERROR => "User_Error",E_USER_WARNING => "User_Warning",E_STRICT => "Runtime_Notice",E_NOTICE => "Notice",
		E_USER_NOTICE => "User_Notice",E_DEPRECATED => "Deprecated" );
		
		/*
		 * neste caso o erro foi suprimido por um @
		 */
		if (error_reporting () === 0) return 0;
		
		/*
		 * Pega apenas os erros importantes, ou seja, ignora os E_Notices.
		 */
		if ($gravidade < 8 || $gravidade == E_CORE_ERROR || $gravidade == E_RECOVERABLE_ERROR)
		{
			if (SYS_MODO_DEVEL) echo "<pre><br /><b>$erros[$gravidade]</b> $message\n\nno arquivo $arquivo Linha: $linha<br /><br /></pre>";
			throw new self ( $message, $erros [$gravidade], array ('arquivo' => $arquivo,'linha' => $linha,'contexto' => $contexto ), true );
		} elseif (SYS_MODO_DEVEL && ! in_array ( $gravidade, array_flip ( $array_ignorar ) )) echo "<pre><br /><b>$erros[$gravidade]</b> $message no arquivo $arquivo Linha: $linha<br /><br /></pre>";
	}

	/**
	 * Método para servir de handler para função shutdown_function do PHP.
	 * Esse método permite avaliar o último erro ocorrido durante a execução para verificar se tratou-se de um fatal
	 * erro.
	 * Na versão atual o fatal erro interrompe imediatamente o código e não executa a função handler de erros. Por isso
	 * até hoje
	 * a classe Exceção não conseguia capturar erros fatais.
	 * 
	 * @return void
	 */
	public static function shutdownFunction()
	{
		$ultimo = error_get_last ();
		if (E_ERROR == $ultimo ['type']) self::erro ( E_ERROR, $ultimo ['message'], $ultimo ['file'], $ultimo ['line'], debug_backtrace () );
	}

	/**
	 * Método para servidr de handler da exceção nativa do PHP e centralizar o controle de erros do sistema
	 * 
	 * @param Exception $Exception Parâmetro passado automaticamente pela engine do PHP.
	 * @return void
	 */
	public static function handlerException($Exception)
	{
		self::erro ( 'Exception', $Exception->getMessage (), $Exception->getFile (), $Exception->getLine (), debug_backtrace () );
	}
}