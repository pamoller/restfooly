<?php

/**
 * rfAuthen - custom access handler for RESTfooly
 * 
 * @author P. Andreas Möller (kontakt@pamoller.com)
 * @version 0.1
 * @license http://opensource.org/licenses/gpl-3.0.html 
 */ 
require_once("RESTfooly.php");

class rfAuthen extends RESTFooly {

  /**
   *  overwrite custom access control method
   */
  protected function httpAccessControlCustom() {
    // start session
    session_start();
    // user is loged in
    if (isset($_SESSION["login"])) {

      return;
    }
    // user tries login
    else if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"]) && $this->dbLoginValid(["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"])) {
      $_SESSION["login"] = true;
		
      return;
    }

    // authentication required
    header('WWW-Authenticate: Basic realm="restfooly"');
    header("HTTP/1.0 401 Unauthorized");
    exit(1);
  }
  
  /**
   * check for login
   * @param string $login
   * @param string $passwd
   */
  protected function dbLoginValid($login, $passwd) {
    
    return isset($this->dbGetOne("SELECT login FROM accounts WHERE login=%s AND passwd=%s", $this->dbEscape($login), $this->dbEscape(md5($passwd)))))?true:false;
  }
}

?>
