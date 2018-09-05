<?php
namespace Esmi\Web;
//require __DIR__ . '/../vendor/autoload.php';
//use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Adapter\AbstractAdapter;
use Aura\Auth\Auth;
use Aura\Auth\Status;
use Aura\Auth\Exception;
//use Esmi\Web\Exception as authException;

//class CustomAdapter implements AdapterInterface
class authAdapter extends AbstractAdapter
{
    // AdapterInterface::login()
    public function login(array $input)
    {
        try {
            if ($this->checkInput($input)){

                if ($this->isLegit($input)) {
                    $username = "abc";
                    $userdata = $this->getUserdata($input['Username']);
                    if ($userdata) {

                    }
                    else {

                    }
                    $this->updateLoginTime(time());
                    return array($username, $userdata);
                }
                else {
                    throw new \Aura\Auth\Exception\UsernameNotFound("isLegit is false!");
                }
            }
            else {
                echo "InvalidInputException(checkInput)...\r\n";
                throw new InvalidInputException("checkInput() return false.!");

            }
        }
        catch(Exception $e) {

            echo ($e->getMessage());
        }
    }

    //user data......................;
    protected function isFoundUsername() {

    }
    protected function getUserdata($username) {
        //return [];
        return false;
    }
    protected function checkUserpassword($username) {

    }
	protected function checkInput($input) {
		if ($input) {
            //echo "checkInput:\r\n";
            var_dump($input);
            foreach($input as $k => $v) {
                if (isset($input[$k]))
                    if (empty($input[$k])) {
                        //throw new authException\InputValueEmpty("Input key($k) value is empty!");
                        throw new Exception("Input key($k) value is empty!");
                        return false;
                    }
                else
                    throw new Exception("Input key(input[$k]) missing!");
            }
			return true;
		}
		else {
            throw new Exception("Input is missgin!");
		}
	}
    // custom support methods not in the interface
    protected function isLegit($input) {
        echo "isLegit:\r\n";
        var_dump($input);
		if (empty($input['Username']))
			return false;
		else {
			return true;
		}
    }

    // AdapterInterface::logout()
    public function logout(Auth $auth, $status = Status::ANON) {
        $this->updateLogoutTime($auth->getUsername(), time());
    }

    // AdapterInterface::resume()
    public function resume(Auth $auth) {
        $this->updateActiveTime($auth->getUsername(), time());
    }


    protected function updateLoginTime($time) {}

    protected function updateActiveTime($time) {}

    protected function updateLogoutTime($time) {}
	// extends functions.
}
?>
