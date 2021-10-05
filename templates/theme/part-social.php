<?php
$social = get_field('social_media','options');
if ($social) :
$social_list = '<ul class="list-inline social-media">';
foreach ($social as $link) {
  switch ($link["platform"]["value"]) {
    case "facebook":
      $class = 'fab fa-facebook-square';
      break;
    case "pinterest":
      $class = 'fab fa-pinterest-p';
      break;
    case "google-plus":
      $class = 'fab fa-google-plus-g';
      break;
    case "angies":
      $class = 'far fa-comment-alt';
      break;
    default:
      $class = 'fab fa-'.$link["platform"]["value"];
  }
  $social_list .= '<li class="list-inline-item"><a href="'.$link['url'].'" target="_blank"><i class="'.$class.'" aria-hidden="true"></i><span class="sr-only">'.$link["platform"]["label"].'</span></a>';
}
$social_list .= '</ul>';
else : $social_list = '';
endif;
echo $social_list;
?>
