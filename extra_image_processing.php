<?php
$imagetype = getimagesize($new_image_path);
if(is_numeric($height) && is_numeric($width))
  {
  $name = $name;
  $destdir = $imagedir.$name;
  switch($imagetype[2])
    {
    case IMAGETYPE_JPEG:
    //$extension = ".jpg";
    $src_img = imagecreatefromjpeg($new_image_path);
    $pass_imgtype = true;
    break;

    case IMAGETYPE_GIF:
    //$extension = ".gif";
    $src_img = imagecreatefromgif($new_image_path);
    $pass_imgtype = true;
    break;

    case IMAGETYPE_PNG:
    //$extension = ".png";
    $src_img = imagecreatefrompng($new_image_path);
    imagesavealpha($src_img,true);
    ImageAlphaBlending($src_img, false);
    $pass_imgtype = true;
    break;

    default:
    move_uploaded_file($new_image_path, ($imagedir.$new_image_path));
    $pass_imgtype = false;
    break;
    }

  if($pass_imgtype === true)
    {
    $source_w = imagesx($src_img);
    $source_h = imagesy($src_img);

    $dst_img = ImageCreateTrueColor($width,$height);
    if($imagetype[2] == IMAGETYPE_PNG)
      {
      imagesavealpha($dst_img,true);
      ImageAlphaBlending($dst_img, false);
      }
    ImageCopyResampled($dst_img,$src_img,0,0,0,0,$width,$height,$source_w,$source_h);
    //exit($destdir);
	$quality = apply_filters( 'jpeg_quality', 75 );
	$quality = apply_filters( 'wpsc_jpeg_quality', $quality );
    switch($imagetype[2])
      {
      case IMAGETYPE_JPEG:
      imagejpeg($dst_img, $destdir, $quality);
      break;

      case IMAGETYPE_GIF:
      imagejpeg($dst_img, $destdir, $quality); //our server doesnt support saving gif, make it save gif images if you need gif images, otherwise, jpeg will do.
      break;

      case IMAGETYPE_PNG:
      imagepng($dst_img, $destdir);
      break;
      }
    usleep(150000);  //wait 0.15 of of a second to process and save the new image
    }
  }
  else
    {
    copy($new_image_path, $imagedir.$name);
    }
?>
