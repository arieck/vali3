<?php
  
class Model_Utils_Captcha
{
  
  private $imageBase = '';
     
  const CPT_FONT = 7;
  const CPT_LENGTH = 5;
  const CPT_NOISE_FACTOR = 0;
  
  
  public function __construct($imagePath)
  {
  
    $this->imageBase = $imagePath . 'captcha/bg';  
  
  } // constructor
  
  
  public function getImage()
  {
  
    $text = $this->getNewText();
                    
    $font = self::CPT_FONT;
    $background = $this->imageBase . rand(0, 9) . '.png';  
    
    list($w, $h) = getimagesize($background);
    $textWidth = strlen($text) * imagefontwidth(self::CPT_FONT);
    $textHeight = imagefontheight(self::CPT_FONT);
    $x = round(($w / 2) - ($textWidth / 2), 1);
    $y = round(($h / 2) - ($textHeight / 2));
        
    $x = rand(1, $w - $textWidth - 1);
    $y = rand(1, $h - $textHeight - 1);
    
    $image = imagecreatefrompng($background);
    $textColor = imagecolorallocate($image, 0, 0, 0);
    
    for ($c = 0; $c < self::CPT_NOISE_FACTOR; ++ $c)
    {
      $x1 = rand(0, $w - 1);
      $y1 = rand(0, $h - 1);
      imagesetpixel($image, $x1, $y1, $textColor);
    }
    
    imagestring($image, self::CPT_FONT, $x, $y, $text, $textColor);
    
    @ob_end_clean();    
    header("Content-type: image/png");  
    imagepng($image);
    imagedestroy($image);
    die();
        
  } // getImage
             
  
  private function getNewText()
  {

    $s = strtolower(substr(md5(rand(0, 9999)), rand(0, 24), self::CPT_LENGTH));
    $s = str_replace('0', 'b', $s);
    $text = str_replace('1', 'f', $s);
    App_Model_Session::captcha($text, false);
    return $text;          
  
  } // getNewText
  

} // end Model_Utils_Captcha 

?>
