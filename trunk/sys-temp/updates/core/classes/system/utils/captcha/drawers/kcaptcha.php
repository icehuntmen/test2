<?php

# KCAPTCHA PROJECT VERSION 1.2.6

# Automatic test to tell computers and humans apart

# Copyright by Kruglov Sergei, 2006, 2007, 2008
# www.captcha.ru, www.kruglov.ru

# System requirements: PHP 4.0.6+ w/ GD

# KCAPTCHA is a free software. You can freely use it for building own site or software.
# If you use this software as a part of own sofware, you must leave copyright notices intact or add KCAPTCHA copyright notices to own.
# As a default configuration, KCAPTCHA has a small credits text at bottom of CAPTCHA image.
# You can remove it, but I would be pleased if you left it. ;)

# See kcaptcha_config.php for customization

	use UmiCms\Service;

	class kcaptchaCaptchaDrawer extends captchaDrawer {
	protected $keystring, $foreground_color, $background_color;
	const alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';
	const fontsdir = 'fonts/kcaptcha';	
	const length = 6;
	const width = 121;
	const height = 60;
	const fluctuation_amplitude = 5;
	const no_spaces = true;
	const show_credits = false;
	const credits = 'www.captcha.ru';
	const jpeg_quality = 90;


	public function __construct() {
		$this->foreground_color = [mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100)];
		$this->background_color = [mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255)];
	}

	public function draw($code) {
		$this->keystring = (string) $code;
		$this->render();
	}


	public function render(){
		$alphabet = self::alphabet;
		$length = self::length;
		$width = self::width;
		$height = self::height;
		$fluctuation_amplitude = self::fluctuation_amplitude;
		$no_spaces = self::no_spaces;
		$show_credits = self::show_credits;
		$foreground_color = $this->foreground_color;
		$background_color = $this->background_color;

		$fonts = [];
		$fontsdir_absolute = dirname(__FILE__) . '/' . self::fontsdir;
		$handle = opendir($fontsdir_absolute);

		if ($handle) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/\.png$/i', $file)) {
					$fonts[] = $fontsdir_absolute.'/' . $file;
				}
			}

		    closedir($handle);
		}
	
		$alphabet_length = mb_strlen($alphabet);
		
		do {
			$font_file = $fonts[mt_rand(0, umiCount($fonts)-1)];
			$font = imagecreatefrompng($font_file);
			imagealphablending($font, true);
			$fontfile_width = imagesx($font);
			$fontfile_height = imagesy($font) - 1;
			$font_metrics = [];
			$symbol = 0;
			$reading_symbol = false;

			// loading font
			for($i = 0;$i < $fontfile_width && $symbol < $alphabet_length; $i++){
				$transparent = (imagecolorat($font, $i, 0) >> 24) == 127;

				if(!$reading_symbol && !$transparent){
					$font_metrics[$alphabet{$symbol}] = ['start' => $i];
					$reading_symbol = true;
					continue;
				}

				if($reading_symbol && $transparent){
					$font_metrics[$alphabet{$symbol}]['end'] = $i;
					$reading_symbol = false;
					$symbol++;
					continue;
				}
			}

			$img=imagecreatetruecolor($width, $height);
			imagealphablending($img, true);
			$white=imagecolorallocate($img, 255, 255, 255);
			$black=imagecolorallocate($img, 0, 0, 0);

			imagefilledrectangle($img, 0, 0, $width-1, $height-1, $white);

			// draw text
			$x=1;
			for($i=0;$i<$length;$i++){
				$m=$font_metrics[$this->keystring{$i}];

				$y=mt_rand(-$fluctuation_amplitude, $fluctuation_amplitude)+($height-$fontfile_height)/2+2;

				if($no_spaces){
					$shift=0;
					if($i>0){
						$shift=10000;
						for($sy=7;$sy<$fontfile_height-20;$sy+=1){
							for($sx=$m['start']-1;$sx<$m['end'];$sx+=1){
				        		$rgb=imagecolorat($font, $sx, $sy);
				        		$opacity=$rgb>>24;
								if($opacity<127){
									$left=$sx-$m['start']+$x;
									$py=$sy+$y;
									if($py>$height) {
										break;
									}
									for($px=min($left,$width-1);$px>$left-12 && $px>=0;$px-=1){
						        		$color=imagecolorat($img, $px, $py) & 0xff;
										if($color+$opacity<190){
											if($shift>$left-$px){
												$shift=$left-$px;
											}
											break;
										}
									}
									break;
								}
							}
						}
						if($shift==10000){
							$shift=mt_rand(4,6);
						}

					}
				}else{
					$shift=1;
				}
				imagecopy($img, $font, $x-$shift, $y, $m['start'], 1, $m['end']-$m['start'], $fontfile_height);
				$x+=$m['end']-$m['start']-$shift;
			}
		}while($x>=$width-10); // while not fit in canvas

		$center=$x/2;

		// credits. To remove, see configuration file
		$img2=imagecreatetruecolor($width, $height+($show_credits?12:0));
		$foreground=imagecolorallocate($img2, $foreground_color[0], $foreground_color[1], $foreground_color[2]);
		$background=imagecolorallocate($img2, $background_color[0], $background_color[1], $background_color[2]);
		imagefilledrectangle($img2, 0, 0, $width-1, $height-1, $background);		
		imagefilledrectangle($img2, 0, $height, $width-1, $height+12, $foreground);
		$credits=empty($credits)?$_SERVER['HTTP_HOST']:$credits;
		imagestring($img2, 2, $width/2-imagefontwidth(2)*mb_strlen($credits)/2, $height-2, $credits, $background);

		// periods
		$rand1=mt_rand(750000,1200000)/10000000;
		$rand2=mt_rand(750000,1200000)/10000000;
		$rand3=mt_rand(750000,1200000)/10000000;
		$rand4=mt_rand(750000,1200000)/10000000;
		// phases
		$rand5=mt_rand(0,31415926)/10000000;
		$rand6=mt_rand(0,31415926)/10000000;
		$rand7=mt_rand(0,31415926)/10000000;
		$rand8=mt_rand(0,31415926)/10000000;
		// amplitudes
		$rand9=mt_rand(330,420)/110;
		$rand10=mt_rand(330,450)/110;

		//wave distortion

		for($x=0;$x<$width;$x++){
			for($y=0;$y<$height;$y++){
				$sx=$x+(sin($x*$rand1+$rand5)+sin($y*$rand3+$rand6))*$rand9-$width/2+$center+1;
				$sy=$y+(sin($x*$rand2+$rand7)+sin($y*$rand4+$rand8))*$rand10;

				if($sx<0 || $sy<0 || $sx>=$width-1 || $sy>=$height-1){
					continue;
				}

				$color=imagecolorat($img, $sx, $sy) & 0xFF;
				$color_x=imagecolorat($img, $sx+1, $sy) & 0xFF;
				$color_y=imagecolorat($img, $sx, $sy+1) & 0xFF;
				$color_xy=imagecolorat($img, $sx+1, $sy+1) & 0xFF;

				if($color==255 && $color_x==255 && $color_y==255 && $color_xy==255){
					continue;
				}

				if($color==0 && $color_x==0 && $color_y==0 && $color_xy==0){
					$newred=$foreground_color[0];
					$newgreen=$foreground_color[1];
					$newblue=$foreground_color[2];
				}else{
					$frsx=$sx-floor($sx);
					$frsy=$sy-floor($sy);
					$frsx1=1-$frsx;
					$frsy1=1-$frsy;

					$newcolor=(
						$color*$frsx1*$frsy1+
						$color_x*$frsx*$frsy1+
						$color_y*$frsx1*$frsy+
						$color_xy*$frsx*$frsy);

					if($newcolor>255) {
						$newcolor = 255;
					}
					$newcolor=$newcolor/255;
					$newcolor0=1-$newcolor;

					$newred=$newcolor0*$foreground_color[0]+$newcolor*$background_color[0];
					$newgreen=$newcolor0*$foreground_color[1]+$newcolor*$background_color[1];
					$newblue=$newcolor0*$foreground_color[2]+$newcolor*$background_color[2];
				}

				imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newred, $newgreen, $newblue));
			}
		}

		$buffer = Service::Response()
			->getCurrentBuffer();
		$filePath = SYS_CACHE_RUNTIME . md5($this->keystring);

		if (function_exists('imagejpeg')){
			$buffer->contentType('image/jpeg');
			imagejpeg($img2, $filePath, self::jpeg_quality);
		} elseif (function_exists('imagegif')){
			$buffer->contentType('image/gif');
			imagegif($img2, $filePath);
		} elseif (function_exists('imagepng')){
			$buffer->contentType('image/x-png');
			imagepng($img2, $filePath);
		}

		$imageContent = file_get_contents($filePath);
		unlink($filePath);

		$buffer->setHeader('Etag', sha1($this->keystring));
		$buffer->push($imageContent);
		$buffer->end();
	}
}