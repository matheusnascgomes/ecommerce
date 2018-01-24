<?php
namespace Hcode;

class Model {

  private $values = [];

  public function __call($name, $args)
  {
    $method = substr($name, 0, 3); //Este trecho captura as primeiras 3 letras do método ex. get / set
    $fieldName = substr($name, 3, strlen($name)); // Este trecho captura o nome do campo (sem o get/set)
    // ucfirst($fieldName);

    switch ($method) {
      case 'get':
         return $this->values[$fieldName];
        break;
      case 'set':
          $this->values[$fieldName] = $args[0];
        break;
    }
  }

  public function setData($data = [])
  {
    foreach ($data as $key => $value) {
      $this->{"set".$key}($value);
    }
  }

  public function getValues()
  {
    return $this->values;
  }
}


 ?>
