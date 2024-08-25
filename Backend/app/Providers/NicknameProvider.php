<?php

namespace App\Providers;

class NicknameProvider extends \Faker\Provider\Base
{
  public function nickname(int $maxLength = 20)
  {
    //choose a random name as base for the nickname; replace spaces with underscores
    $baseName = str_replace(' ', '_', $this->generator->name());

    //calculate the length of the name and substract 1 for _ and leave rest for numbers 
    //10 to power of spaces - 1 is the currect top boundary
    $lengthForNumbers = $maxLength - 1 - strlen($baseName);
    if($lengthForNumbers>0){
      $randomNumber = $this->generator->numberBetween(0,pow(10, $lengthForNumbers) - 1);
      return $baseName . '_' . $randomNumber;
    }else if($lengthForNumbers==0){
      return $baseName;
    }else{
      return substr($baseName, 0, $maxLength - 1);
    }
  }
}