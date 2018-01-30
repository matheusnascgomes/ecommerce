<?php
namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;


class User extends Model{

  const SESSION = "User";
  const SECRET = "HcodePhp7_Secret";

  public static function login($login, $password)
  {
    $sql = new Sql();

    $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN",[
      ":LOGIN"=>$login
    ]);

    if(count($results) === 0){
      throw new \Exception("Usuário inexistente ou senha inválida!");
    }

    $data = $results[0];

    if(password_verify($password, $data["despassword"]) === true){

      $user = new User();

      $user->setData($data);

      $_SESSION[User::SESSION] = $user->getValues();

      return $user;

    } else {
      throw new \Exception("Usuário inexistente ou senha inválida!");
    }

  }

  public static function verifyLogin($inadmin = true)
  {
    if(
        !isset($_SESSION[User::SESSION])
        ||
        !$_SESSION[User::SESSION]
        ||
        !(int)$_SESSION[User::SESSION]["iduser"] > 0
        ||
        (bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
    ){
      header("Location: /admin/login");
      exit;
    }
  }

  public static function logout()
  {
    $_SESSION[User::SESSION] = NULL;

  }

  public static function listAll()
  {
    $sql = new Sql();

    return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

  }

  public static function getForgot($email, $inadmin = true)
  {
    $sql = new Sql();

    $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desmail = :email",[
      ":email"=>$email
    ]);

    if (count($results) === 0) {

      throw new \Exception("Não foi possível recuperar a senha.");

    } else {

      $data = $results[0];

      $results2 = $sql("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",[
        ":iduser"=>$data['iduser'],
        ":desip"=>$_SERVER['REMOTE_ADDR']
      ]);

      if (count($results2) === 0) {

        throw new \Exception("Não foi possível recuperar a senha.");

      } else {

        $dataRecovery = $results2[0];

        // Code recovery as hash
        $code = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));

        if ($inadmin == true) {

          $link = "http://local.hcodeecommerce.com.br/admin/forgot/reset?code=$code";

        } else {

          $link = "http://local.hcodeecommerce.com.br/forgot/reset?code=$code";

        }

        $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha da Hcode Store", "forgot", [
          "name"=>$data["desperson"],
          "link"=>$link
        ]);

        $mailer->send();

        return $data;

      }
    }
  }

  public static function validForgotDecrypt($code)
  {
    $idrecovery = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);

    $sql = new Sql();

		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE
				a.idrecovery = :idrecovery
			    AND
			    a.dtrecovery IS NULL
			    AND
			    DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
		", [
			":idrecovery"=>$idrecovery
		]);

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{

			return $results[0];

		}
  }

  public static function setFogotUsed($idrecovery)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", [
			":idrecovery"=>$idrecovery
		]);

	}

  public function setPassword($password)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", [
			":password"=>$password,
			":iduser"=>$this->getiduser()
		]);

	}

}

?>
