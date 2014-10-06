<?php
/**
 * Este arquivo contem o componente RedirecionamentoObrigatorio
 *
 * @package Controller.Component
 */
/**
 * Componente para controle de redirecionamentos obrigatórios
 *
 * @package Controller.Component
 */
class RedirecionamentoObrigatorioComponent extends Component
{
    /**
     * Lista de componentens com regra de redirecionamento, usado por este component
     * Essa informação é definida no controller
     *
     * @var array
     */
    private $_redirecionamentos = array();

    /**
     * Lista de ações (controller e action) que deve ser permitido o acesso, ignorando qualquer obrigatoriedade
     * Essa informação é definida no controller, a execução do compoentes será baseado na ordem do array
     *
     * @var array
     */
    private $_acoesIgnorar = array();

    /**
     * Controller que está usando este component
     *
     * @var Controller
     */
    private $_controller;

    /**
     * CakeRequest
     *
     * @var CakeRequest
     */
    private $_request;

    /**
     * Url atual contendo controller e action
     *
     * @var array
     */
    private $_urlAtual;

    /**
     * Lista de components usados, terá os componentes com as regras de redirecinamento
     *
     * @var array
     */
    public $components = array();

    /**
     * Constructor
     *
     * @param ComponentCollection $collection A ComponentCollection this component can use to lazy load its components
     * @param array               $settings   Array of configuration settings.
     *
     * @access public
     * @return void
     */
    public function __construct(ComponentCollection $collection, $settings = array())
    {
        $settings += array('redirecionamentos' => array(), 'acoesIgnorar' => array());

        if ( !is_array($settings['acoesIgnorar']) ) {
            throw new InvalidArgumentException('A lista de ações permitidas deve ser um array válido');
        }
        $this->_acoesIgnorar = $settings['acoesIgnorar'];

        if ( !is_array($settings['redirecionamentos']) || empty($settings['redirecionamentos']) ) {
            throw new InvalidArgumentException('Componente(s) de redirecionamento deve(m) ser informado(s)');
        }

        $this->_redirecionamentos = $this->components = $settings['redirecionamentos'];

        parent::__construct($collection, $settings);
    }

    /**
     * Callback executado depois de Controller::beforeFilter() e antes da ação do controller
     *
     * @param Controller $controller Controller que está usando o component
     *
     * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::startup
     * @return void
     */
    public function startup(Controller $controller)
    {
        $this->_controller = $controller;
        $this->_request = $controller->request;
        $this->_executar();
    }

    /**
     * Realiza as lógicas de redirecionamento, na orde
     *
     * @access private
     * @return null
     */
    private function _executar()
    {
        $this->_urlAtual = array(
            'controller' => strtolower($this->_request->params['controller']),
            'action' => strtolower($this->_request->params['action'])
        );

        if ( $this->_verificarAcaoAtualIgnorada() ) {
            return;
        }

        foreach ( $this->_redirecionamentos as $component ) {
            $component = $this->$component;
            if ( !$component->conferirRegra($this->_controller) ) {
                continue;
            }

            $url = $component->url();

            if ( !$this->_verificarUrlAtual($url) ) {
                $this->_controller->redirect($url);
            }
            return;
        }
    }

    /**
     * Verifica se a url informada é a url atual. Baseado apenas no controller e action
     *
     * @param array $url URL contendo controller e action
     *
     * @access private
     * @return boolean
     */
    private function _verificarUrlAtual($url)
    {
        if ( strtolower($url['controller']) != $this->_urlAtual['controller'] ) {
            return false;
        }

        if ( strtolower($url['action']) != $this->_urlAtual['action'] ) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se para ação atual deve ser ignorado a regras de redirecionamento
     *
     * @access private
     * @return boolean
     */
    public function _verificarAcaoAtualIgnorada()
    {
        foreach ( $this->_acoesIgnorar as $acao ) {
            if ( $this->_verificarUrlAtual($acao) ) {
                return true;
            }
        }
        return false;
    }
}