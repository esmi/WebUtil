<?php
namespace Esmi\Web;

use Esmi\Blade;

use Aura\Auth\Exception;
use Aura\Session\SessionFactory;

use Illuminate\Http\Request;
use Rakit\Validation\ErrorBag;
use Symfony\Component\HttpFoundation\Session\Session;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\NotFoundException;

use Tidy;

use Esmi\DB\dbConfig;


//require_once "Menu.php";


class WebApp extends Blade{

	protected $menu=null;
    protected $errors;
    
    protected $messageFactory;
    
    protected $request;
    protected $response;
    
    protected $session;

    protected $session_factory;
    
    protected $segment=null;
    
	protected $auth = null;
    protected $org;
    protected $vw_notlogin = "notlogined";
	
	protected $checks=[];
	//protected $machine='default';
	//protected $machine='evan';


    
    public function __construct($segname=null, $menu=null) {
        //$menu = new Menu();
        if ( $menu )
            $this->menu =  $menu->getMenu();
        
        $this->request = Request::capture();  //laravel request.
        $this->messageFactory = MessageFactoryDiscovery::find();
        $this->response = $this->messageFactory->createResponse();
        //echo "response--: \r\n";
        //var_dump($this->response);
        
        $this->errors = new ErrorBag();

        $this->session_factory = new \Aura\Session\SessionFactory;
        $this->session = $this->session_factory->newInstance($_COOKIE);
        
        //var_dump($this->session);
        if ( !$this->session->isStarted()) {
            $this->session->start();
        }

        if (! is_null($segname)) {
            $this->setSegment($segname);
        }
        
        //var_dump($_SESSION);
            
        //var_dump($this->segment);
        //echo "name--: " . $this->old('name') . "\r\n";
   		$this->checks = $this->getChecks();

        parent::__construct();
    }
    protected function setSegment($segname) {
        if ( $this->session ) {
            $this->segment = $this->session->getsegment($segname);
        }
    }
    protected function getSegment() {
        return $this->segment;
    }
    protected function getChecks() {
		$columns = $this->columns();
		$checks = [];
		foreach( $columns as $c) {
			if (isset($c['valid'])){
				$checks[ $c['field'] ] = $c['valid'];
			}		
		}
		return $checks;
	}
    protected function getAlias() {
		$columns = $this->columns();
		$alias = [];
		foreach( $columns as $c) {
			if (isset($c['field'])){
                if (isset($c['display']))
				    $alias[ $c['field'] ] = "'" . $c['display'] . "':";
			}		
		}
		return $alias;
    }
    protected function ruleMessages() {
        return 
            [
                'required' => ':attribute 需要輸入',
                'min' => ':attribute 最少長度(:min)個字',
                'max' => ':attribute 最大長度為(:max)個字',
                'email' => ':email 格式不正確',
                'date' =>":attribute 日期格式不正確",
                'in' => ":attribute (:value)不符選取規則",
                'between' => ':attribute 長度需介於(:min)個字之間',
                'numeric' => ':attribute 此欄位必須為數值',
                'required_with' => ':attribute 需要輸入',
                'different' => ':attribute 與 :field 是相同的內容',
           ];
    }
	protected function isCheckFormError() {
		return $this->checks != [];
	}
	
    function make($blade, $extras=[]) {
	
        //var_dump($extras);
        //echo "name^^: " . $this->old('name') . "\r\n";
        
        $data = [
                "variable1" => "value1",
                "menu"      => $this->menu,
                "errors"  => $this->errors,
                "request" => $this->request,
                "fds" => $this,
             ];
        $data = array_merge($data, $extras );
        //var_dump($data);
        $html = $this->run( $blade, $data ); // call bladeone::run();
        //$html = $this->tidy($html);
        echo $html;
	}
    function tidy($html) {
		//echo extension_loaded('tidy') ? "LOADED" : "NOT LOADED";
		$config = [
           	'indent'         => true,
           	//'output-xhtml'   => false,
           	//'output-xhtml'   => true,
			//'escape-cdata' => true,
           	'wrap'           => 200
		];

		// Tidy
		$tidy = new Tidy();
		$tidy->parseString($html, $config, 'utf8');
		$tidy->cleanRepair();

		// Output
		return $tidy;
    }
    
    function flashExcept( $variables = []) {
        if ( ! $this->segment)
            return;
        
        if ( ! is_array($variables)) {
            $variables = [] + [$variables];
        }

        $all = $this->request->all();
        $keys = array_keys($all);
        foreach( $keys as $v) {
            if (! in_array($v, $variables))
                $this->segment->set($v, $all[$v]);
        }
    }
    function flashOnly( $variables = []) {
        if ( ! $this->segment)
            return;
        if ( ! is_array($variables)) {
            $variables = [] + [$variables];
        }
        if ( is_array($variables)) {
            foreach( $variables as $v) {
                 $this->segment->set($v, $this->request->input($v));
            }
        }
    }
    function old( $field ) {
        return $this->segment ? $this->segment->get($field) : '';
    }
    function checked($field, $val) {
        return $this->old($field) == $val ? "checked='checked'" : "";
    }
    function do() {
        //$q = new Request();
        //$request = $q->capture();
        $method = $this->request->method;
        if ($this->islogin()) {
            if (!$method)
                $this->make($this->view, $this->viewData());
            else {
				//$checkValid = isset($this->request->valid) ? $this->request->valid : false;
				$valid = $this->request->valid;
				//var_dump($valid);
				if ($valid) {
					if ( $this->isCheckFormError()) {
						//if ($this->valid( $formData, $this->formRules)) {
						$errorBag = $this->chkError();
						if ($errorBag->count() > 0) {
							//var_dump($errorBag);
                            //TODO: transform message.
							$messages = $errorBag->all();
							//$messages =  $errorBag->toArray();
							//var_dump($messages);
							echo json_encode(['status' => 'FORM_CHECKER', 'message' => implode(', ', $messages)]);
                            return;
						}
					}
				}
                $this->dataRunner($this->runner);
            }
        }
        else {
            $this->notlogin();
        }
    }
    function islogin() {
        return $this->org->islogin();
        // or call $this->auth->islogin();
        // or call Auth::islogin();
    }
    function dataRunner($runner) {
        $DB = new dbConfig();
        $cls = new $runner();   // call App/runner() object.
        $cls->run();
    }
    function notlogin() {
        $this->make($this->vw_notlogin);
    }
}
