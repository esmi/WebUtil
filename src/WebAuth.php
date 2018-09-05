<?php
namespace Esmi\Web;

//require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../../../vendor/autoload.php";

use Illuminate\Http\Request;
use Rakit\Validation\Validator;

use Aura\Auth\Exception;
use Katzgrau\KLogger\Logger;
use Esmi\DB\dbConfig;
use Esmi\Web\WebApp;
use Esmi\Web\AppUtils;

//TODO:
// 1. use Esmi\Web\WebApp.php instead "require_once 'webApp.php';"
// 2. before log check Auth::
// 3. after login Auth::setAuth()
// 4. after logout run Auth::logout() and Auth::clearAuth().
//
//require_once "webApp.php";
//class login extends webApp {
abstract class WebAuth extends WebApp {
    //protected $messageFactory;
    use AppUtils;

	//protected $messageFactory;
    protected $response;

	public $auth = null;

	protected $viewfile;
    protected $adapter;
    protected $segname ;
    protected $logs;

    /*
    class myAuthAdapter extends authAdapter{}
    class myLogin extends WebAuth{
        public function __construct(){
            parent::__construct("mylogin", new myAuthAdapter);
        }

    }
    $login = new myLogin();
    $login->do();
    */
    public function __construct($viewfile=null, $authAdapter=null, $segname=null, $logs=null) {
		$this->viewfile = $viewfile ?: "login";
		$this->segname = $segname ?: get_called_class() . "\auth";

        parent::__construct($this->segname);
        //$this->authAdapter = $authAdapter ?: new authAdapter ;
        $this->logs = $logs ?: __DIR__ . "/logs";
        $this->authAdapter = $authAdapter ?: $this->setAdapter(); //new authAdapter ;
    }
    // set $this->authAdapter;
    protected function setAdapter($authAdapter=null) {
        if ($authAdapter)
            $this->authAdapter = $authAdapter;
        else {
            $data = $this->data();
            if ( isset($data['adapter'])) {
                $this->authAdapter = $data['adapter'];
            }
            else {
                $this->authAdapter = new authAdapter;
            }
        }
    }
	/*
	function data() {
	// client define:
		return [
            'adapter' => $adapter;
			'runner' => 'App\member_runner',
			'input' => [
				'account' => 'account',
				'password' => 'password',
				'email' => 'email',
			];
		];
	}
	*/
	abstract protected function data();

	function input() {
		$data = $this->data();
		if ( $data ) {
			if ( isset($data['input']))
				return $data['input'];
		}
		return null;
	}
	//檢查 data()['input'] 是否都有'post'出來
	function allPosted() {
		return !($this->hasNotPosted());
	}
	function hasNotPosted() {
		$input = $this->input();
		var_dump($input);
		$err = "";
		if ( $input ) {
			foreach( $input as $k => $v ) {
				if (!isset($_POST[$v]))
					$err = ( $err == "" ?: "," )
						. "Warning >>> element: [$k], not posted!";
			}
		}
		return ( $err == "") ? null : $err;
	}
	function hasInput() {
		$input = $this->input();

		if ( $input ) {
			$t = true;
			foreach( $input as $k => $v ) {
				//echo "input key: " . $k . "\r\n";
				$t = $t && isset($_POST[$v]);
			}
			return $t;
		}
		else
			return false;
	}

    function rules() {
		return [];
		/*
        return [
            'account'     => 'required',
            'password'    => 'required|min:6',
        ];
		*/
    }

    function chkError() {

        $request = $this->request;

        if ($request->isMethod('POST')) {

            $validator = new Validator;
            $validation = $validator->make($_POST + $_FILES, $this->rules());
            $validation->validate();

            if ($validation->fails())
                $this->errors = $validation->errors();
        }
        return $this->errors;
    }
    protected function getChecks() {
        return false;
    }
    function debugAuth($auth) {

        if ($auth) {
            echo "auth: \r\n";
            var_dump($auth);

            echo 'status: ' . $auth->getStatus() . "\r\n";
            echo "user name: " . $auth->getUserName() . "\r\n";
            echo "user data: " ;
            var_dump($auth->getUserData());
            echo "\r\n";

            echo "first Active: " . $auth->getFirstActive() . "\r\n";
            echo "last Active: " . $auth->getLastActive() . "\r\n";

            switch (true) {
                case $auth->isAnon():
                    echo "You are not logged in.";
                    break;
                case $auth->isIdle():
                    echo "Your session was idle for too long. Please log in again.";
                    break;
                case $auth->isExpired():
                    echo "Your session has expired. Please log in again.";
                    break;
                case $auth->isValid():
                    echo "You are still logged in.";
                    break;
                default:
                    echo "You have an unknown status.";
                    break;
            }
        }
        else {
            if ($auth === null) {
                echo "\$auth is null\r\n";
            }
            else
                echo "Auth($auth)\r\n";
        }
    }

	function doLogin() {
		//$log = new Katzgrau\KLogger\Logger($this->logs);
		$log = new Logger($this->logs);

		$auth_factory = new \Aura\Auth\AuthFactory($_COOKIE);
		$auth = $auth_factory->newInstance();

        //$adapter = new devAdapter;

		$login_service = $auth_factory->newLoginService($this->authAdapter);
		$logout_service = $auth_factory->newLogoutService($this->authAdapter);
		$resume_service = $auth_factory->newResumeService($this->authAdapter);

		try{
            $account = $_POST["account"];
			try {

				$data = $this->data();
				$input = [];
				if ( isset($data['input'])) {
					foreach( $data['input'] as $k => $v) {
						$input[$k] = $_POST[$v];
					}
					var_dump($input);
					$login_service->login($auth, $input );

					return $auth;
				}
				else
					throw new InvalidInputException("The 'input' data is not set.");


			} catch (\Aura\Auth\Exception\UsernameMissing $e) {

				$log->notice("The 'username:' field is missing or empty." . $e->getMessage());
				throw new InvalidLoginException("The 'username:' field is missing or empty.");

			} catch (\Aura\Auth\Exception\PasswordMissing $e) {

				$log->notice("The 'password' field is missing or empty.");
				throw new InvalidLoginException("The 'passowrd' field is missing or empty!");

			} catch (\Aura\Auth\Exception\UsernameNotFound $e) {

				$log->warning("The account:$account you entered was not found.");
				throw new InvalidLoginException("username was not found.");

			} catch (\Aura\Auth\Exception\MultipleMatches $e) {

				$log->warning("There is more than one account with that username.");
				throw new InvalidLoginException("There is more than one account with that username");

			} catch (\Aura\Auth\Exception\PasswordIncorrect $e) {

				$log->notice("The account:$account password you entered was incorrect.");
				throw new InvalidLoginException("Password is incorrect!");

			} catch (\Aura\Auth\Exception\ConnectionFailed $e) {

				$log->notice("Cound not connect to IMAP or LDAP server.");
				$log->info("This could be because the username or password was wrong,");
				$log->info("or because the the connect operation itself failed in some way. ");
				$log->info($e->getMessage());
				throw new InvalidLoginException("Connection failed!");

			} catch (\Aura\Auth\Exception\BindFailed $e) {

				$log->notice("Cound not bind to LDAP server.");
				$log->info("This could be because the username or password was wrong,");
				$log->info("or because the the bind operation itself failed in some way. ");
				$log->info($e->getMessage());
				throw new InvalidLoginException("Bind failed!");
			}

			catch (InvalidLoginException $e) {
				$log->notice("InvalidLoginException: " . $e->getMessage() . ".");


				echo "Invalid login details. Please try again a.";
			}
		}

		catch (InvalidLoginException $e ) {
			//echo "Invalid login details. Please try again b.";
			$log->notice("InvalidLoginException2: " . $e->getMessage() . ".");
			return NULL;
		}

	}
	function setAuth($auth) {
		$this->auth = $auth;
	}
    function processLogout() {

    }
    function processLogin() {
		echo "do processLogin....\r\n";
        $auth = $this->doLogin();
		echo "return auth():\r\n";
		var_dump($auth);
        $this->setAuth($auth);

        //$this->debugAuth($this->auth);

        if ($auth) {

            if ($this->auth->isValid()) {
                echo "Successfully, login OK.....!!!!\r\n";
                //Auth::setAuth($auth);
                //return $response;
            }
            else {
                //$response->withHeader("Location", "abc.php");
            }
        }
        else {
            //echo "login failure.....!!!!\r\n";
            $this->errors->add("login","failure", "login auth failure");
            //echo "<pre>";
            //print_r($this->errors->firstOfAll());
            //echo "</pre>";
            $this->make($this->viewfile);
        }
    }
    function do() {
		//$hasInput = $this->hasInput();
		echo "error count(): " . $this->errors->count() . "\r\n";
		echo "this->hasInput(): \r\n";
		var_dump($this->hasInput());
		echo "\r\nthis->allPosted(): \r\n";
		var_dump($this->allPosted());

       	if ( ($this->errors->count() > 0) || ( !($this->hasInput() && $this->allPosted())) ) {
			echo "make this->viewfile\r\n";
            $this->make($this->viewfile);
		}
        else {
            $this->processLogin();
        }
    }
}
