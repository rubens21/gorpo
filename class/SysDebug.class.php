<?php

/**
 * Classe que facilita debugar o código permitindo armazenar informações sobre variáveis no decorrer do dódigo prara
 * facilitar sua visualização no final.
 *
 * @author Rubens de Souza Silva <rubens21@gmail.com>
 * @package System
 * @subpackage Control
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class SysDebug
{

	/**
	 * lista de variáveis que foram adicionadas no decorrer do código.
	 *
	 * @var array
	 */
	private static $check_points = array();

	/**
	 * Tempo inicial do debug.
	 *
	 * @var int
	 */
	private static $debug_time_start;

	/**
	 * Tempo final do debug.
	 *
	 * @var int
	 */
	private static $debug_time_last;

	/**
	 * Listagem com os tempos de debug.
	 *
	 * @var array
	 */
	private static $debug_times = array();

	/**
	 * Para garantir que não será possível instanciar um objeto dessa classe.
	 */
	private function __construct()
	{
	
	}

	/**
	 * Pemrite adicionar variáveis que serão apresentadas apenas quando for solicitado o resumo do debug.
	 *
	 * @param mixed $var Variável que será apresentada desmembrada para facilitar o entendimento do erro.
	 * @param string $label (optional) Nome dado a essa inclusão para facilitar identificação.
	 *        return void
	 */
	public static function addErro($var, $label = null)
	{
		$caller = debug_backtrace()[0];
		self::$check_points[] = array('HTML' => self::dump($var, false, false), 'stringy'=>  var_export($var, true), 'VAR' => $var,'Label' => $label, 'file' => $caller['file'], 'line' => $caller['line']);
	}

	/**
	 * Exibe a lista de erros em um breve resumo.
	 *
	 * @return void
	 */
	public static function resumeExit()
	{
		$lista = $menu  = '';
		foreach(self::$check_points as $pos => $error)
		{
			$menu .= '<li><a href="#item_'.$pos.'">'. ($error['Label']?:'Item '.($pos + 1) ).'</a></li>';
			$lista .= '<a name="item_'.$pos.'">' .($pos + 1) . ' - <strong>' . $error['Label'] . '</strong></a>' . $error['HTML'];
		}
		$html = '<br/><strong>' . count(self::$check_points) . ' Dumps</strong><br/>';
		$html .= '<ol>'.$menu. '</ol><br/>';
		$html .= $lista;
		echo $html;
		exit();
	}

	/**
	 * Obtém a lista dos erros adicionados até então (útil para enviar em outros formados como Json).
	 *
	 * @return array Lista dos erros onde cada item possui a variável, seu rótulo e sua apresentação desmembrada.
	 */
	public static function getErros()
	{
		return self::$check_points;
	}

	/**
	 * Despesa imediatamente a variável.
	 * Por padrão, os parâmetros irão ordenar a exibição na tela e o encerramento do código.
	 * 
	 * @param unknown $var
	 * @param string $print
	 * @param string $exit
	 * @return string
	 */
	public static function dump($var, $print = true, $exit = true)
	{
		$caller = array_shift(debug_backtrace());
		$return = "<pre>" . highlight_string("<?php\n //".$caller['file'].': '.$caller['line']."\n\n" . var_export($var, true) . " \n?>", true) . "</pre>";
		if ($print)
		{
			echo $return;
			if ($exit)
				exit();
		} else
			return $return;
	}

	/**
	 * Realiza o calculo do tempo de realização de um debug.
	 *
	 * @param mixed $label Rotina a sofrer debug.
	 * @param bool $imprimir Determina se será exibido resultado em tela.
	 * @return void
	 */
	public static function custoTempoDebug($label, $imprimir = false)
	{
		if (! self::$debug_time_start)
			self::$debug_time_start = microtime();
		if (! self::$debug_time_last)
			self::$debug_time_last = microtime();
		$acumulado = microtime() - self::$debug_time_start;
		$parcial = microtime() - self::$debug_time_last;
		self::$debug_time_last = microtime();
		self::$debug_times[] = array('acumulado' => $acumulado,'parcial' => $parcial,'label' => $label);
		if ($imprimir)
			echo "\n$label: $acumulado/$parcial\n";
	}

	/**
	 * Exbibe o tempo de debug em formato separado por ponto-e-virgula "CSV".
	 *
	 * @return void
	 */
	public static function custoTempoDebugExporte()
	{
		foreach(self::$debug_times as $ordem => $marco)
			$csv[] = $ordem . ',' . $marco['label'] . ',' . $marco['acumulado'] . ',' . $marco['parcial'];
		echo implode("\n", $csv);
	}

}

